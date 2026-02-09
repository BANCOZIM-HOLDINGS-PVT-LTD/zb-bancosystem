<?php

namespace App\Filament\Resources\CustomerManagementResource\Pages;

use App\Filament\Resources\CustomerManagementResource;
use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions;
use Illuminate\Support\Facades\Response;

class ReportsDownload extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string $resource = CustomerManagementResource::class;
    
    protected static string $view = 'filament.resources.customer-management-resource.pages.reports-download';
    
    protected static ?string $title = 'Customer Reports';
    
    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Campaigns')
                ->url(CustomerManagementResource::getUrl('index'))
                ->color('gray'),
        ];
    }
    
    public function downloadInstallmentsDue(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getInstallmentsDueData();
        
        return Response::streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Reference Code', 'Product', 'Delivered At']);
            
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['name'] ?? 'N/A',
                    $row['phone'] ?? 'N/A',
                    $row['reference_code'] ?? 'N/A',
                    $row['product'] ?? 'N/A',
                    $row['delivered_at'] ?? 'N/A',
                ]);
            }
            
            fclose($handle);
        }, 'installments_due_' . now()->format('Y-m-d') . '.csv');
    }
    
    public function downloadIncompleteApplications(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getIncompleteApplicationsData();
        
        return Response::streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Reference Code', 'Current Step', 'Last Activity']);
            
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['name'] ?? 'N/A',
                    $row['phone'] ?? 'N/A',
                    $row['reference_code'] ?? 'N/A',
                    $row['current_step'] ?? 'N/A',
                    $row['last_activity'] ?? 'N/A',
                ]);
            }
            
            fclose($handle);
        }, 'incomplete_applications_' . now()->format('Y-m-d') . '.csv');
    }
    
    public function downloadRegisteredUsers(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getRegisteredUsersData();
        
        return Response::streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'National ID', 'Registered At', 'Current Step']);
            
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['name'] ?? 'N/A',
                    $row['phone'] ?? 'N/A',
                    $row['national_id'] ?? 'N/A',
                    $row['created_at'] ?? 'N/A',
                    $row['current_step'] ?? 'N/A',
                ]);
            }
            
            fclose($handle);
        }, 'registered_users_' . now()->format('Y-m-d') . '.csv');
    }
    
    protected function getInstallmentsDueData(): array
    {
        return DeliveryTracking::query()
            ->join('application_states', 'delivery_trackings.application_state_id', '=', 'application_states.id')
            ->where('delivery_trackings.status', 'delivered')
            ->whereNotNull('delivery_trackings.recipient_phone')
            ->select([
                'delivery_trackings.recipient_name as name',
                'delivery_trackings.recipient_phone as phone',
                'application_states.reference_code',
                'delivery_trackings.product_type as product',
                'delivery_trackings.delivered_at',
            ])
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'phone' => $row->phone,
                'reference_code' => $row->reference_code,
                'product' => $row->product,
                'delivered_at' => $row->delivered_at?->format('Y-m-d H:i') ?? null,
            ])
            ->toArray();
    }
    
    protected function getIncompleteApplicationsData(): array
    {
        return ApplicationState::query()
            ->where('is_archived', false)
            ->whereNotNull('form_data')
            ->where('current_step', '!=', 'completed')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Unknown',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                    'reference_code' => $app->reference_code,
                    'current_step' => $app->current_step,
                    'last_activity' => $app->last_activity?->format('Y-m-d H:i') ?? null,
                ];
            })
            ->values()
            ->toArray();
    }
    
    protected function getRegisteredUsersData(): array
    {
        return ApplicationState::query()
            ->whereNotNull('form_data')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Unknown',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                    'national_id' => $formData['nationalId'] ?? '',
                    'created_at' => $app->created_at?->format('Y-m-d H:i') ?? null,
                    'current_step' => $app->current_step,
                ];
            })
            ->values()
            ->toArray();
    }
    
    public function getInstallmentsDueCount(): int
    {
        return DeliveryTracking::where('status', 'delivered')
            ->whereNotNull('recipient_phone')
            ->count();
    }
    
    public function getIncompleteApplicationsCount(): int
    {
        return ApplicationState::where('is_archived', false)
            ->whereNotNull('form_data')
            ->where('current_step', '!=', 'completed')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->count();
    }
    
    public function getRegisteredUsersCount(): int
    {
        return ApplicationState::whereNotNull('form_data')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->count();
    }
}
