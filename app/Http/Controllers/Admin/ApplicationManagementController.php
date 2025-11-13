<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationState;
use App\Repositories\ApplicationStateRepository;
use App\Services\PDFGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationManagementController extends Controller
{
    private ApplicationStateRepository $repository;
    private PDFGeneratorService $pdfGenerator;

    public function __construct(ApplicationStateRepository $repository, PDFGeneratorService $pdfGenerator)
    {
        $this->repository = $repository;
        $this->pdfGenerator = $pdfGenerator;
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
}
