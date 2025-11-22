<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Services\PDFGeneratorService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export SSB Applications to CSV')
                ->icon('heroicon-o-arrow-down-on-square')
                ->action(function () {
                    // Export logic - ONLY SSB applications
                    $applications = ApplicationResource::getEloquentQuery()->get();

                    $csvData = [];
                    $csvData[] = [
                        'REFERENCE', 'ID NUMBER', 'EC NUMBER', 'TYPE',
                        'END DATE', 'AMOUNT', 'START DATE'
                    ];

                    foreach ($applications as $application) {
                        $formData = $application->form_data;
                        $formResponses = $formData['formResponses'] ?? [];

                        // Filter: Only export SSB applications
                        // SSB applications have responsibleMinistry field
                        if (!isset($formData['responsibleMinistry']) && !isset($formResponses['responsibleMinistry'])) {
                            continue; // Skip non-SSB applications
                        }

                        // REFERENCE - Auto-generated ID like ZB2025000008
                        $reference = 'ZB' . date('Y') . str_pad($application->id, 6, '0', STR_PAD_LEFT);

                        // ID NUMBER - National ID from form responses
                        $idNumber = $formResponses['idNumber']
                                 ?? $formResponses['nationalIdNumber']
                                 ?? $formResponses['nationalId']
                                 ?? 'N/A';

                        // EC NUMBER - Employment Number
                        $ecNumber = $formResponses['employmentNumber'] ?? 'N/A';

                        // TYPE - Always "NEW"
                        $type = 'NEW';

                        // AMOUNT - Loan amount
                        $amount = number_format($formData['finalPrice'] ?? 0, 2);

                        // Calculate loan repayment dates
                        $loanTenure = (int)($formResponses['loanTenure'] ?? 12); // Default 12 months
                        $startDate = $application->created_at;
                        $endDate = (clone $startDate)->addMonths($loanTenure);

                        $csvData[] = [
                            $reference,
                            $idNumber,
                            $ecNumber,
                            $type,
                            $endDate->format('Y-m-d'),
                            $amount,
                            $startDate->format('Y-m-d'),
                        ];
                    }

                    $filename = 'ssb_applications_export_' . date('Ymd_His') . '.csv';
                    $path = storage_path('app/public/' . $filename);

                    $fp = fopen($path, 'w');
                    foreach ($csvData as $row) {
                        fputcsv($fp, $row);
                    }
                    fclose($fp);

                    return response()->download($path, $filename)->deleteFileAfterSend();
                }),
                
            Actions\Action::make('bulk_pdf_generation')
                ->label('Bulk PDF Generation')
                ->icon('heroicon-o-document')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('form_type')
                        ->label('Form Type')
                        ->options([
                            'all' => 'All Form Types',
                            'ssb' => 'SSB Forms',
                            'zb_account_opening' => 'ZB Account Opening Forms',
                            'account_holders' => 'Account Holder Forms',
                            'sme_business' => 'SME Business Forms',
                        ])
                        ->required()
                        ->default('all')
                        ->helperText('Select which type of forms to generate PDFs for'),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Application Status')
                        ->options([
                            'all' => 'All Completed Applications',
                            'approved' => 'Approved Applications Only',
                            'in_review' => 'In Review Applications',
                            'pending_documents' => 'Pending Documents',
                        ])
                        ->required()
                        ->default('all'),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('From Date'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                    \Filament\Forms\Components\Checkbox::make('include_documents')
                        ->label('Include Uploaded Documents')
                        ->helperText('Include applicant documents in the generated PDFs'),
                ])
                ->action(function (array $data) {
                    $query = ApplicationResource::getEloquentQuery();

                    // Apply status filter
                    if ($data['status'] !== 'all') {
                        $query->where('current_step', $data['status']);
                    }

                    // Apply date filters
                    if ($data['date_from']) {
                        $query->whereDate('created_at', '>=', $data['date_from']);
                    }

                    if ($data['date_to']) {
                        $query->whereDate('created_at', '<=', $data['date_to']);
                    }

                    $applications = $query->get();

                    // Apply form type filter
                    if ($data['form_type'] !== 'all') {
                        $applications = $applications->filter(function ($application) use ($data) {
                            return static::detectFormType($application) === $data['form_type'];
                        });
                    }

                    $count = $applications->count();
                    
                    if ($count === 0) {
                        Notification::make()
                            ->title('No Applications Found')
                            ->body('No applications match your filter criteria.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    try {
                        $pdfGenerator = app(PDFGeneratorService::class);
                        $pdfPaths = [];
                        
                        // Generate PDFs for each application
                        foreach ($applications as $application) {
                            $pdfPath = $pdfGenerator->generateApplicationPDF(
                                $application, 
                                ['include_documents' => $data['include_documents'] ?? false]
                            );
                            $pdfPaths[] = $pdfPath;
                        }
                        
                        // Create a ZIP file if there are multiple PDFs
                        if (count($pdfPaths) > 1) {
                            $zipFilename = 'applications_' . date('Ymd_His') . '.zip';
                            $zipPath = storage_path('app/public/' . $zipFilename);
                            
                            $zip = new ZipArchive();
                            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                                foreach ($pdfPaths as $pdfPath) {
                                    $zip->addFile(
                                        Storage::disk('public')->path($pdfPath), 
                                        basename($pdfPath)
                                    );
                                }
                                $zip->close();
                                
                                Notification::make()
                                    ->title("Generated {$count} PDFs Successfully")
                                    ->body('PDFs have been packaged into a ZIP file for download.')
                                    ->success()
                                    ->send();
                                    
                                return response()->download($zipPath, $zipFilename)->deleteFileAfterSend();
                            } else {
                                throw new \Exception('Failed to create ZIP file');
                            }
                        } else if (count($pdfPaths) === 1) {
                            // Download single PDF directly
                            $pdfPath = $pdfPaths[0];
                            
                            Notification::make()
                                ->title('PDF Generated Successfully')
                                ->success()
                                ->send();
                                
                            return response()->download(
                                Storage::disk('public')->path($pdfPath),
                                basename($pdfPath)
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error('Bulk PDF Generation failed: ' . $e->getMessage(), [
                            'filter_data' => $data,
                            'exception' => $e,
                        ]);
                        
                        Notification::make()
                            ->title('Bulk PDF Generation Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Applications'),
            
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(ApplicationResource::getModel()::whereDate('created_at', today())->count()),
                
            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                ->badge(ApplicationResource::getModel()::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()),
                
            'pending_review' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_step', 'in_review'))
                ->badge(ApplicationResource::getModel()::where('current_step', 'in_review')->count()),
                
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_step', 'approved'))
                ->badge(ApplicationResource::getModel()::where('current_step', 'approved')->count()),
                
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('current_step', 'rejected'))
                ->badge(ApplicationResource::getModel()::where('current_step', 'rejected')->count()),
                
            'whatsapp' => Tab::make('WhatsApp')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('channel', 'whatsapp'))
                ->badge(ApplicationResource::getModel()::where('channel', 'whatsapp')->count()),
                
            'web' => Tab::make('Web')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('channel', 'web'))
                ->badge(ApplicationResource::getModel()::where('channel', 'web')->count()),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ApplicationStatsWidget::class,
        ];
    }

    /**
     * Detect form type from application data
     */
    protected static function detectFormType($application): string
    {
        // Check metadata first
        if (isset($application->metadata['form_type'])) {
            return $application->metadata['form_type'];
        }

        // Detect from form data
        $formData = $application->form_data;
        $formResponses = $formData['formResponses'] ?? $formData;

        if (isset($formData['responsibleMinistry']) || isset($formResponses['responsibleMinistry'])) {
            return 'ssb';
        } elseif (isset($formData['businessName']) || isset($formData['businessRegistration']) ||
                  isset($formResponses['businessName']) || isset($formResponses['businessRegistration'])) {
            return 'sme_business';
        } elseif (isset($formData['accountType']) || isset($formResponses['accountType'])) {
            return 'zb_account_opening';
        } else {
            return 'account_holders';
        }
    }
}
