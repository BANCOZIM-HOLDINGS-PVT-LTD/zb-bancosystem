<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationState;
use App\Repositories\ApplicationStateRepository;
use App\Services\PDFGeneratorService;
use App\Services\SSBStatusService;
use App\Services\ZBStatusService;
use App\Enums\SSBLoanStatus;
use App\Enums\ZBLoanStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationManagementController extends Controller
{
    private ApplicationStateRepository $repository;
    private PDFGeneratorService $pdfGenerator;
    private SSBStatusService $ssbStatusService;
    private ZBStatusService $zbStatusService;

    public function __construct(
        ApplicationStateRepository $repository,
        PDFGeneratorService $pdfGenerator,
        SSBStatusService $ssbStatusService,
        ZBStatusService $zbStatusService
    ) {
        $this->repository = $repository;
        $this->pdfGenerator = $pdfGenerator;
        $this->ssbStatusService = $ssbStatusService;
        $this->zbStatusService = $zbStatusService;
    }

    /**
     * Display applications list
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search', 'channel', 'status', 'date_from', 'date_to', 
            'employer', 'form_id', 'current_step'
        ]);

        $applications = $this->repository->getPaginatedOptimized($filters, 20);

        // Transform data for frontend
        $applications->getCollection()->transform(function ($app) {
            $formData = $app->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];
            
            return [
                'id' => $app->id,
                'session_id' => $app->session_id,
                'channel' => $app->channel,
                'current_step' => $app->current_step,
                'user_identifier' => $app->user_identifier,
                'applicant_name' => trim(($formResponses['firstName'] ?? '') . ' ' . ($formResponses['lastName'] ?? '')),
                'email' => $formResponses['emailAddress'] ?? null,
                'mobile' => $formResponses['mobile'] ?? null,
                'employer' => $formData['employer'] ?? null,
                'form_id' => $formData['formId'] ?? null,
                'loan_amount' => $formResponses['loanAmount'] ?? null,
                'status' => $this->determineApplicationStatus($app),
                'created_at' => $app->created_at,
                'updated_at' => $app->updated_at,
                'expires_at' => $app->expires_at,
                'reference_code' => $app->reference_code,
            ];
        });

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'filters' => $filters,
            'statistics' => $this->getApplicationStatistics(),
        ]);
    }

    /**
     * Show specific application
     */
    public function show(string $sessionId): Response
    {
        $application = $this->repository->findBySessionId($sessionId);
        
        if (!$application) {
            abort(404, 'Application not found');
        }

        $transitions = $application->transitions()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Applications/Show', [
            'application' => $this->formatApplicationDetails($application),
            'transitions' => $transitions,
            'notes' => $this->getApplicationNotes($application),
        ]);
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,under_review,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);
        
        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            // Update metadata with new status
            $metadata = $application->metadata ?? [];
            $metadata['admin_status'] = $request->status;
            $metadata['status_updated_at'] = now()->toISOString();
            $metadata['status_updated_by'] = auth()->id();

            $application->update(['metadata' => $metadata]);

            // Add note if provided
            if ($request->notes) {
                $this->addApplicationNote($application, $request->notes, 'status_update');
            }

            Log::info('Application status updated', [
                'session_id' => $sessionId,
                'old_status' => $metadata['admin_status'] ?? 'pending',
                'new_status' => $request->status,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update application status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update application status',
            ], 500);
        }
    }

    /**
     * Add note to application
     */
    public function addNote(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:1000',
            'type' => 'nullable|string|in:general,follow_up,issue,resolution',
        ]);

        $application = $this->repository->findBySessionId($sessionId);
        
        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->addApplicationNote(
                $application, 
                $request->note, 
                $request->type ?? 'general'
            );

            return response()->json([
                'success' => true,
                'message' => 'Note added successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add note',
            ], 500);
        }
    }

    /**
     * Download application PDF
     */
    public function downloadPdf(string $sessionId)
    {
        $application = $this->repository->findBySessionId($sessionId);
        
        if (!$application) {
            abort(404, 'Application not found');
        }

        try {
            $pdfResult = $this->pdfGenerator->generatePDF($application, [
                'include_admin_notes' => true,
                'watermark' => 'ADMIN COPY',
            ]);

            return response()->download(
                storage_path('app/public/' . $pdfResult['file_path']),
                "application_{$sessionId}.pdf"
            );

        } catch (\Exception $e) {
            Log::error('Failed to generate admin PDF', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to generate PDF');
        }
    }

    /**
     * Bulk actions on applications
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete,export',
            'session_ids' => 'required|array|min:1',
            'session_ids.*' => 'string',
        ]);

        try {
            $count = 0;
            
            foreach ($request->session_ids as $sessionId) {
                $application = $this->repository->findBySessionId($sessionId);
                
                if (!$application) {
                    continue;
                }

                switch ($request->action) {
                    case 'approve':
                        $this->updateApplicationStatus($application, 'approved');
                        $count++;
                        break;
                    case 'reject':
                        $this->updateApplicationStatus($application, 'rejected');
                        $count++;
                        break;
                    case 'delete':
                        $application->delete();
                        $count++;
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed on {$count} applications",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk action failed',
            ], 500);
        }
    }

    /**
     * Get application statistics
     */
    private function getApplicationStatistics(): array
    {
        return [
            'total' => ApplicationState::count(),
            'pending' => ApplicationState::whereJsonContains('metadata->admin_status', 'pending')->count(),
            'under_review' => ApplicationState::whereJsonContains('metadata->admin_status', 'under_review')->count(),
            'approved' => ApplicationState::whereJsonContains('metadata->admin_status', 'approved')->count(),
            'rejected' => ApplicationState::whereJsonContains('metadata->admin_status', 'rejected')->count(),
            'today' => ApplicationState::whereDate('created_at', today())->count(),
            'this_week' => ApplicationState::where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    /**
     * Format application details for frontend
     */
    private function formatApplicationDetails(ApplicationState $application): array
    {
        $formData = $application->form_data ?? [];
        $formResponses = $formData['formResponses'] ?? [];
        $metadata = $application->metadata ?? [];

        return [
            'id' => $application->id,
            'session_id' => $application->session_id,
            'channel' => $application->channel,
            'current_step' => $application->current_step,
            'user_identifier' => $application->user_identifier,
            'form_data' => $formData,
            'form_responses' => $formResponses,
            'metadata' => $metadata,
            'status' => $this->determineApplicationStatus($application),
            'created_at' => $application->created_at,
            'updated_at' => $application->updated_at,
            'expires_at' => $application->expires_at,
            'reference_code' => $application->reference_code,
            'reference_code_expires_at' => $application->reference_code_expires_at,
        ];
    }

    /**
     * Determine application status
     */
    private function determineApplicationStatus(ApplicationState $application): string
    {
        $metadata = $application->metadata ?? [];
        return $metadata['admin_status'] ?? 'pending';
    }

    /**
     * Get application notes
     */
    private function getApplicationNotes(ApplicationState $application): array
    {
        $metadata = $application->metadata ?? [];
        return $metadata['notes'] ?? [];
    }

    /**
     * Add note to application
     */
    private function addApplicationNote(ApplicationState $application, string $note, string $type = 'general'): void
    {
        $metadata = $application->metadata ?? [];
        $notes = $metadata['notes'] ?? [];

        $notes[] = [
            'id' => uniqid(),
            'note' => $note,
            'type' => $type,
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()->name ?? 'System',
            'created_at' => now()->toISOString(),
        ];

        $metadata['notes'] = $notes;
        $application->update(['metadata' => $metadata]);
    }

    /**
     * Update application status
     */
    private function updateApplicationStatus(ApplicationState $application, string $status): void
    {
        $metadata = $application->metadata ?? [];
        $metadata['admin_status'] = $status;
        $metadata['status_updated_at'] = now()->toISOString();
        $metadata['status_updated_by'] = auth()->id();

        $application->update(['metadata' => $metadata]);
    }

    // ==================== SSB LOAN WORKFLOW METHODS ====================

    /**
     * Get SSB application status
     */
    public function getSSBStatus(string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $statusDetails = $this->ssbStatusService->getStatusDetailsForClient($application);

        return response()->json([
            'success' => true,
            'data' => $statusDetails,
        ]);
    }

    /**
     * Client checks status by reference code
     */
    public function checkStatusByReference(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json([
                'error' => 'Application not found',
                'message' => 'No application found with this reference code',
            ], 404);
        }

        $statusDetails = $this->ssbStatusService->getStatusDetailsForClient($application);

        return response()->json([
            'success' => true,
            'data' => array_merge($statusDetails, [
                'reference_code' => $application->reference_code,
                'created_at' => $application->created_at,
            ]),
        ]);
    }

    /**
     * Initialize SSB workflow for application
     */
    public function initializeSSBWorkflow(Request $request, string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->ssbStatusService->initializeSSBApplication($application);

            return response()->json([
                'success' => true,
                'message' => 'SSB workflow initialized successfully',
                'status' => $this->ssbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize SSB workflow', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initialize SSB workflow',
            ], 500);
        }
    }

    /**
     * Adjust loan period (for insufficient salary or contract expiry)
     */
    public function adjustLoanPeriod(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
            'new_period' => 'required|integer|min:1|max:60',
            'adjustment_type' => 'required|in:salary,contract',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->ssbStatusService->adjustLoanPeriod(
                $application,
                $request->new_period,
                $request->adjustment_type
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Cannot adjust period at this time',
                    'message' => 'Your application is not in a state that allows period adjustment',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Loan period adjusted successfully. Your application has been resubmitted to SSB.',
                'new_status' => $this->ssbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to adjust loan period', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to adjust loan period',
            ], 500);
        }
    }

    /**
     * Update ID number
     */
    public function updateIDNumber(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
            'id_number' => 'required|string|regex:/^[0-9]{2}-[0-9]{6,7}[A-Z][0-9]{2}$/',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->ssbStatusService->updateIDNumber($application, $request->id_number);

            if (!$success) {
                return response()->json([
                    'error' => 'Cannot update ID number at this time',
                    'message' => 'Your application is not in a state that requires ID correction',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'ID number updated successfully. Your application has been resubmitted to SSB.',
                'new_status' => $this->ssbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update ID number', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update ID number',
            ], 500);
        }
    }

    /**
     * Decline adjustment and cancel application
     */
    public function declineAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->ssbStatusService->declineAdjustmentAndCancel($application);

            return response()->json([
                'success' => true,
                'message' => 'Your application has been cancelled as per your request.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to decline adjustment', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process your request',
            ], 500);
        }
    }

    /**
     * Upload and process SSB CSV response file
     */
    public function uploadSSBCSVResponse(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $fileName = 'ssb_response_' . now()->format('Y-m-d_His') . '.csv';
            $filePath = $file->storeAs('ssb_responses', $fileName);

            $fullPath = storage_path('app/' . $filePath);

            $results = $this->ssbStatusService->parseAndProcessSSBCSV($fullPath);

            // Archive the file
            Storage::move($filePath, 'ssb_responses/processed/' . $fileName);

            Log::info('SSB CSV processed', $results);

            return response()->json([
                'success' => true,
                'message' => 'SSB CSV response processed successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process SSB CSV', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process SSB CSV file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually update SSB status (admin only)
     */
    public function manualSSBStatusUpdate(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'ssb_response_data' => 'nullable|array',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            // Validate status exists
            try {
                $status = SSBLoanStatus::from($request->status);
            } catch (\ValueError $e) {
                return response()->json([
                    'error' => 'Invalid status provided',
                ], 400);
            }

            $this->ssbStatusService->updateStatus(
                $application,
                $status,
                $request->notes ?? 'Manually updated by admin',
                $request->ssb_response_data ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'SSB status updated successfully',
                'new_status' => $status->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to manually update SSB status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update SSB status',
            ], 500);
        }
    }

    /**
     * Simulate SSB response (for testing without actual SSB integration)
     */
    public function simulateSSBResponse(Request $request, string $sessionId): JsonResponse
    {
        if (!app()->environment(['local', 'development', 'testing'])) {
            return response()->json(['error' => 'Not available in production'], 403);
        }

        $request->validate([
            'response_type' => 'required|in:approved,insufficient_salary,invalid_id,contract_expiring,rejected',
            'recommended_period' => 'nullable|integer|min:1|max:60',
            'salary' => 'nullable|numeric',
            'contract_expiry_date' => 'nullable|date',
            'error_message' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        // Validate required fields for specific response types
        if ($request->response_type === 'insufficient_salary' && !$request->recommended_period) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'recommended_period is required for insufficient_salary response',
            ], 400);
        }

        if ($request->response_type === 'contract_expiring' && (!$request->contract_expiry_date || !$request->recommended_period)) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'contract_expiry_date and recommended_period are required for contract_expiring response',
            ], 400);
        }

        try {
            $ssbResponse = [
                'response_type' => $request->response_type,
                'recommended_period' => $request->recommended_period,
                'salary' => $request->salary,
                'contract_expiry_date' => $request->contract_expiry_date,
                'error_message' => $request->error_message,
                'reason' => $request->reason,
            ];

            $this->ssbStatusService->processSSBResponse($application, $ssbResponse);

            return response()->json([
                'success' => true,
                'message' => 'SSB response simulated successfully',
                'new_status' => $this->ssbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to simulate SSB response', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to simulate SSB response',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get SSB status history
     */
    public function getSSBStatusHistory(string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $history = $metadata['ssb_status_history'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'current_status' => $this->ssbStatusService->getCurrentStatus($application)?->value,
                'history' => $history,
            ],
        ]);
    }

    /**
     * Export SSB applications for submission to SSB
     */
    public function exportSSBApplicationsCSV(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'employer']);

        $applications = ApplicationState::query()
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->where('created_at', '<=', $dateTo);
            })
            ->where(function ($query) {
                $query->whereJsonContains('metadata->ssb_status', SSBLoanStatus::AWAITING_SSB_APPROVAL->value)
                    ->orWhereJsonContains('metadata->ssb_status', SSBLoanStatus::PERIOD_ADJUSTED_RESUBMITTED->value)
                    ->orWhereJsonContains('metadata->ssb_status', SSBLoanStatus::ID_CORRECTED_RESUBMITTED->value)
                    ->orWhereJsonContains('metadata->ssb_status', SSBLoanStatus::CONTRACT_PERIOD_ADJUSTED_RESUBMITTED->value);
            })
            ->get();

        $fileName = 'ssb_submissions_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'reference_code',
                'first_name',
                'last_name',
                'id_number',
                'mobile',
                'email',
                'employer',
                'loan_amount',
                'loan_period',
                'status',
                'submission_date',
            ]);

            foreach ($applications as $application) {
                $formData = $application->form_data ?? [];
                $formResponses = $formData['formResponses'] ?? [];

                fputcsv($file, [
                    $application->reference_code,
                    $formResponses['firstName'] ?? '',
                    $formResponses['lastName'] ?? '',
                    $formResponses['idNumber'] ?? '',
                    $formResponses['mobile'] ?? '',
                    $formResponses['emailAddress'] ?? '',
                    $formData['employer'] ?? '',
                    $formResponses['loanAmount'] ?? '',
                    $formResponses['loanPeriod'] ?? '',
                    $application->metadata['ssb_status'] ?? '',
                    $application->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==================== ZB LOAN WORKFLOW METHODS ====================

    /**
     * Initialize ZB workflow for application
     */
    public function initializeZBWorkflow(Request $request, string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->zbStatusService->initializeZBApplication($application);

            return response()->json([
                'success' => true,
                'message' => 'ZB workflow initialized successfully',
                'status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize ZB workflow', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initialize ZB workflow',
            ], 500);
        }
    }

    /**
     * Get ZB application status
     */
    public function getZBStatus(string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $statusDetails = $this->zbStatusService->getStatusDetailsForClient($application);

        return response()->json([
            'success' => true,
            'data' => $statusDetails,
        ]);
    }

    /**
     * Client checks ZB status by reference code
     */
    public function checkZBStatusByReference(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json([
                'error' => 'Application not found',
                'message' => 'No application found with this reference code',
            ], 404);
        }

        $statusDetails = $this->zbStatusService->getStatusDetailsForClient($application);

        return response()->json([
            'success' => true,
            'data' => array_merge($statusDetails, [
                'reference_code' => $application->reference_code,
                'created_at' => $application->created_at,
            ]),
        ]);
    }

    /**
     * Admin processes credit check - Good
     */
    public function processCreditCheckGood(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processCreditCheckGood(
                $application,
                $request->notes ?? ''
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process credit check',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Credit check processed - Good - Approved',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process credit check good', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process credit check',
            ], 500);
        }
    }

    /**
     * Admin processes credit check - Poor
     */
    public function processCreditCheckPoor(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processCreditCheckPoor(
                $application,
                $request->notes ?? ''
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process credit check',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Credit check processed - Poor - Client offered blacklist report',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process credit check poor', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process credit check',
            ], 500);
        }
    }

    /**
     * Admin processes salary not regular rejection
     */
    public function processSalaryNotRegular(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processSalaryNotRegular(
                $application,
                $request->notes ?? ''
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process salary check',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application rejected - Salary not deposited regularly',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process salary not regular', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process salary check',
            ], 500);
        }
    }

    /**
     * Admin processes insufficient salary rejection
     */
    public function processInsufficientSalary(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'recommended_period' => 'required|integer|min:1|max:60',
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processInsufficientSalary(
                $application,
                $request->recommended_period,
                $request->notes ?? ''
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process insufficient salary',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application rejected - Insufficient salary - Client offered period adjustment',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process insufficient salary', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process insufficient salary',
            ], 500);
        }
    }

    /**
     * Admin processes approved application
     */
    public function processZBApproved(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processApproved(
                $application,
                $request->notes ?? ''
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process approval',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application approved - Client can track delivery after 24 hours',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process ZB approved', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process approval',
            ], 500);
        }
    }

    /**
     * Client declines blacklist report
     */
    public function declineBlacklistReport(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->zbStatusService->declineBlacklistReport($application);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your interest. Kindly reapply when circumstances in your credit rating have changed.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to decline blacklist report', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process your request',
            ], 500);
        }
    }

    /**
     * Client requests blacklist report
     */
    public function requestBlacklistReport(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $result = $this->zbStatusService->requestBlacklistReport($application);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Failed to request blacklist report', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process your request',
            ], 500);
        }
    }

    /**
     * Process blacklist report payment (webhook/callback)
     */
    public function processBlacklistReportPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
            'payment_reference' => 'required|string',
            'blacklist_institutions' => 'nullable|array',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->processBlacklistReportPayment(
                $application,
                $request->payment_reference,
                $request->blacklist_institutions ?? []
            );

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to process payment',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment received. Blacklist report will be sent shortly.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process blacklist payment', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process payment',
            ], 500);
        }
    }

    /**
     * Client declines period adjustment
     */
    public function declineZBPeriodAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $this->zbStatusService->declinePeriodAdjustment($application);

            return response()->json([
                'success' => true,
                'message' => 'Application declined. Thank you for your interest.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to decline period adjustment', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process your request',
            ], 500);
        }
    }

    /**
     * Client accepts period adjustment
     */
    public function acceptZBPeriodAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_code' => 'required|string',
        ]);

        $application = ApplicationState::where('reference_code', $request->reference_code)->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        try {
            $success = $this->zbStatusService->acceptPeriodAdjustment($application);

            if (!$success) {
                return response()->json([
                    'error' => 'Failed to adjust period',
                    'message' => 'Could not find recommended period or adjust application',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application resubmitted with adjusted period. Check again after 24 hours.',
                'new_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to accept period adjustment', [
                'reference_code' => $request->reference_code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process your request',
            ], 500);
        }
    }

    /**
     * Get ZB status history
     */
    public function getZBStatusHistory(string $sessionId): JsonResponse
    {
        $application = $this->repository->findBySessionId($sessionId);

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        $metadata = $application->metadata ?? [];
        $history = $metadata['zb_status_history'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'current_status' => $this->zbStatusService->getCurrentStatus($application)?->value,
                'history' => $history,
            ],
        ]);
    }
}
