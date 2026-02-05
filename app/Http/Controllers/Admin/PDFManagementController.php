<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response as ResponseFacade;
use ZipArchive;
use Carbon\Carbon;

class PDFManagementController extends Controller
{
    protected PDFGeneratorService $pdfGenerator;
    
    public function __construct(PDFGeneratorService $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }
    
    /**
     * List all generated PDFs with filtering options
     */
    public function index(Request $request)
    {
        $query = ApplicationState::query()
            ->whereNotNull('form_data')
            ->with(['agent'])
            ->orderBy('created_at', 'desc');
        
        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filter by form type
        if ($request->has('form_type')) {
            $query->whereJsonContains('metadata->form_type', $request->form_type);
        }
        
        // Filter by channel
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }
        
        // Filter by completion status
        if ($request->has('status')) {
            $query->where('current_step', $request->status);
        }
        
        $applications = $query->paginate(25);
        
        return response()->json([
            'applications' => $applications,
            'stats' => [
                'total_pdfs' => $this->getTotalPDFCount(),
                'pdfs_today' => $this->getPDFCountToday(),
                'pdfs_this_month' => $this->getPDFCountThisMonth(),
                'storage_used' => $this->getStorageUsed()
            ]
        ]);
    }
    
    /**
     * Download individual PDF
     */
    public function download(Request $request, string $sessionId)
    {
        $application = ApplicationState::where('session_id', $sessionId)->firstOrFail();
        
        try {
            // Get form type from metadata or detect from form data
            $formType = $this->detectFormType($application);
            
            // Generate PDF
            $result = $this->pdfGenerator->generatePDF($application, ['formType' => $formType, 'admin' => true]);
            $pdfPath = $result['path'] ?? null;
            
            if (!$pdfPath || !Storage::disk('public')->exists($pdfPath)) {
                return response()->json(['error' => 'PDF not found or could not be generated'], 404);
            }
            
            $filename = $this->generatePDFFilename($application, $formType);
            
            return ResponseFacade::download(
                Storage::disk('public')->path($pdfPath),
                $filename,
                ['Content-Type' => 'application/pdf']
            );
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk download multiple PDFs as ZIP
     */
    public function bulkDownload(Request $request)
    {
        $request->validate([
            'session_ids' => 'required|array',
            'session_ids.*' => 'exists:application_states,session_id'
        ]);
        
        $applications = ApplicationState::whereIn('session_id', $request->session_ids)->get();
        
        if ($applications->isEmpty()) {
            return response()->json(['error' => 'No applications found'], 404);
        }
        
        // Create temporary ZIP file
        $zipFileName = 'bancozim_pdfs_' . Carbon::now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Cannot create ZIP file'], 500);
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($applications as $application) {
            try {
                $formType = $this->detectFormType($application);
                $result = $this->pdfGenerator->generatePDF($application, ['formType' => $formType, 'admin' => true]);
                $pdfPath = $result['path'] ?? null;
                
                if ($pdfPath && Storage::disk('public')->exists($pdfPath)) {
                    $filename = $this->generatePDFFilename($application, $formType);
                    $zip->addFile(Storage::disk('public')->path($pdfPath), $filename);
                    $successCount++;
                } else {
                    $errors[] = "PDF not generated for session {$application->session_id}";
                }
                
            } catch (\Exception $e) {
                $errors[] = "Error processing session {$application->session_id}: " . $e->getMessage();
            }
        }
        
        $zip->close();
        
        if ($successCount === 0) {
            return response()->json([
                'error' => 'No PDFs could be generated',
                'errors' => $errors
            ], 500);
        }
        
        return ResponseFacade::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
    
    /**
     * Export for bank processing with specific formatting
     */
    public function exportForBank(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'format' => 'in:pdf,excel,csv'
        ]);

        $applications = ApplicationState::whereBetween('created_at', [
            $request->date_from,
            $request->date_to
        ])->get();

        // Apply form type filter for PDF and Excel exports
        $formTypeFilter = $request->input('form_type', 'all');
        if ($formTypeFilter !== 'all' && $request->format !== 'csv') {
            $applications = $applications->filter(function ($app) use ($formTypeFilter) {
                return $this->detectFormType($app) === $formTypeFilter;
            });
        }

        $format = $request->format ?? 'pdf';

        switch ($format) {
            case 'pdf':
                return $this->exportBankPDFs($applications, $formTypeFilter);
            case 'excel':
                return $this->exportBankExcel($applications, $formTypeFilter);
            case 'csv':
                // CSV always exports SSB only
                return $this->exportBankCSV($applications);
            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }
    
    /**
     * Get PDF generation statistics
     */
    public function statistics()
    {
        $stats = [
            'total_applications' => ApplicationState::count(),
            'pdfs_generated_today' => $this->getPDFCountToday(),
            'pdfs_generated_this_month' => $this->getPDFCountThisMonth(),
            'storage_usage' => $this->getStorageUsed(),
            'form_type_breakdown' => $this->getFormTypeBreakdown(),
            'channel_breakdown' => $this->getChannelBreakdown(),
            'completion_rates' => $this->getCompletionRates()
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Clean up old PDF files
     */
    public function cleanup(Request $request)
    {
        $days = $request->input('days', 30); // Default 30 days
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        // Get files older than cutoff date
        $oldApplications = ApplicationState::where('created_at', '<', $cutoffDate)->get();
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($oldApplications as $application) {
            try {
                // Delete associated PDF files
                $pdfPattern = "applications/*{$application->session_id}*.pdf";
                $files = Storage::glob($pdfPattern);
                
                foreach ($files as $file) {
                    Storage::delete($file);
                    $deletedCount++;
                }
                
            } catch (\Exception $e) {
                $errors[] = "Error cleaning up session {$application->session_id}: " . $e->getMessage();
            }
        }
        
        return response()->json([
            'message' => "Cleanup completed",
            'files_deleted' => $deletedCount,
            'errors' => $errors
        ]);
    }
    
    /**
     * Regenerate PDF for an application
     */
    public function regenerate(string $sessionId)
    {
        $application = ApplicationState::where('session_id', $sessionId)->firstOrFail();
        
        try {
            $formType = $this->detectFormType($application);
            $result = $this->pdfGenerator->generatePDF($application, ['formType' => $formType, 'force' => true, 'admin' => true]); // Force regenerate
            $pdfPath = $result['path'] ?? null;
            
            return response()->json([
                'message' => 'PDF regenerated successfully',
                'path' => $pdfPath,
                'download_url' => route('admin.pdf.download', $sessionId)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to regenerate PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Protected Helper Methods
    
    protected function detectFormType(ApplicationState $application): string
    {
        // Check metadata first
        if (isset($application->metadata['form_type'])) {
            return $application->metadata['form_type'];
        }
        
        // Detect from form data
        $formData = $application->form_data;
        
        if (isset($formData['responsibleMinistry'])) {
            return 'ssb';
        } elseif (isset($formData['businessName']) || isset($formData['businessRegistration'])) {
            return 'sme_business';
        } elseif (isset($formData['accountType'])) {
            return 'zb_account_opening';
        } else {
            return 'account_holders';
        }
    }
    
    protected function generatePDFFilename(ApplicationState $application, string $formType): string
    {
        $formData = $application->form_data;
        $name = isset($formData['firstName'], $formData['surname']) 
            ? $formData['firstName'] . '_' . $formData['surname']
            : 'User_Unknown';
        
        $date = $application->created_at->format('Ymd');
        
        return "{$name}_{$formType}_Application_{$date}.pdf";
    }
    
    protected function getTotalPDFCount(): int
    {
        return ApplicationState::whereNotNull('form_data')->count();
    }
    
    protected function getPDFCountToday(): int
    {
        return ApplicationState::whereDate('created_at', Carbon::today())
            ->whereNotNull('form_data')
            ->count();
    }
    
    protected function getPDFCountThisMonth(): int
    {
        return ApplicationState::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereNotNull('form_data')
            ->count();
    }
    
    protected function getStorageUsed(): string
    {
        $files = Storage::allFiles('applications');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }
        
        return $this->formatBytes($totalSize);
    }
    
    protected function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    protected function getFormTypeBreakdown(): array
    {
        return ApplicationState::selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.form_type')) as form_type,
                COUNT(*) as count
            ")
            ->groupBy('form_type')
            ->pluck('count', 'form_type')
            ->toArray();
    }
    
    protected function getChannelBreakdown(): array
    {
        return ApplicationState::selectRaw('channel, COUNT(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();
    }
    
    protected function getCompletionRates(): array
    {
        $total = ApplicationState::count();
        $completed = ApplicationState::where('current_step', 'completed')->count();
        
        return [
            'total_applications' => $total,
            'completed_applications' => $completed,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }
    
    protected function exportBankPDFs($applications, $formTypeFilter = 'all')
    {
        // Implementation for bank-specific PDF export format
        // This would create a standardized format for bank processing

        $formTypeName = $formTypeFilter !== 'all' ? $formTypeFilter . '_' : '';
        $zipFileName = 'bank_' . $formTypeName . 'export_' . Carbon::now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Cannot create ZIP file'], 500);
        }

        foreach ($applications as $application) {
            try {
                $formType = $this->detectFormType($application);
                $result = $this->pdfGenerator->generatePDF($application, ['formType' => $formType, 'admin' => true]);
                $pdfPath = $result['path'] ?? null;

                if ($pdfPath && \Storage::disk('public')->exists($pdfPath)) {
                    $filename = $this->generatePDFFilename($application, $formType);
                    $zip->addFile(\Storage::disk('public')->path($pdfPath), $filename);
                }
            } catch (\Exception $e) {
                \Log::error("Error adding PDF to bank export: " . $e->getMessage());
            }
        }

        $zip->close();

        return ResponseFacade::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    protected function exportBankExcel($applications, $formTypeFilter = 'all')
    {
        // Implementation for Excel export
        // Would use Laravel Excel package

        return response()->json(['message' => 'Excel export not yet implemented']);
    }
    
    protected function exportBankCSV($applications)
    {
        // Filter for SSB applications only
        $ssbApplications = $applications->filter(function ($app) {
            return $this->detectFormType($app) === 'ssb';
        });

        // Implementation for CSV export - SSB ONLY
        $filename = 'ssb_applications_export_' . Carbon::now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($ssbApplications) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Session ID', 'Name', 'Email', 'Phone', 'Form Type',
                'Channel', 'Status', 'Created At', 'Monthly Payment', 'Loan Tenure',
                'Responsible Ministry', 'National ID'
            ]);

            foreach ($ssbApplications as $app) {
                $formData = $app->form_data;
                $formResponses = $formData['formResponses'] ?? $formData;

                fputcsv($file, [
                    $app->session_id,
                    ($formData['firstName'] ?? $formResponses['firstName'] ?? '') . ' ' . ($formData['surname'] ?? $formResponses['surname'] ?? ''),
                    $formData['emailAddress'] ?? $formResponses['emailAddress'] ?? '',
                    $formData['mobile'] ?? $formResponses['mobile'] ?? '',
                    'SSB',
                    $app->channel,
                    $app->current_step,
                    $app->created_at->format('Y-m-d H:i:s'),
                    $formData['monthlyPayment'] ?? $formResponses['monthlyPayment'] ?? '',
                    $formData['loanTenure'] ?? $formResponses['loanTenure'] ?? '',
                    $formData['responsibleMinistry'] ?? $formResponses['responsibleMinistry'] ?? '',
                    $formData['nationalIdNumber'] ?? $formResponses['nationalIdNumber'] ?? $formResponses['idNumber'] ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}