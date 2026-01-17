<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ApplicationWizardController;
use App\Http\Controllers\ApplicationPDFController;
use App\Http\Controllers\WelcomeController;

// Health check endpoint for Fly.io
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/', [WelcomeController::class, 'index'])->name('home');

Route::get('/terms-and-conditions', function () {
    return Inertia::render('TermsAndConditions');
})->name('terms.conditions');

Route::get('/download-ssb-form', [PdfController::class, 'downloadSsbForm'])->name('download.ssb.form');
Route::get('/download-zb-account-form', [PdfController::class, 'downloadZbAccountForm'])->name('download.zb.account.form');
Route::get('/download-account-holders-form', [PdfController::class, 'downloadAccountHoldersForm'])->name('download.account.holders.form');
Route::get('/download-sme-account-opening-form', [PdfController::class, 'downloadSmeAccountOpeningForm'])->name('download.sme.account.opening.form');

// Admin Portal Landing Page
Route::get('/admin/portal', function () {
    return view('admin.portal');
})->name('admin.portal');


// Application Wizard Routes
Route::get('/application', [ApplicationWizardController::class, 'show'])->name('application.wizard');
Route::get('/apply', [ApplicationWizardController::class, 'showWithReferral'])->name('application.apply'); // Agent referral entry point
Route::get('/application/resume/{identifier}', [ApplicationWizardController::class, 'resume'])->name('application.resume');
Route::get('/application/status', [ApplicationWizardController::class, 'status'])->name('application.status');
Route::get('/application/success', [ApplicationWizardController::class, 'success'])->name('application.success');
Route::get('/delivery/tracking', [ApplicationWizardController::class, 'tracking'])->name('delivery.tracking');
Route::get('/reference-code', [ApplicationWizardController::class, 'referenceCodeLookup'])->name('reference.code.lookup');

// Deposit Payment Routes
Route::post('/deposit/initiate', [\App\Http\Controllers\DepositPaymentController::class, 'initiatePayment'])->name('deposit.initiate');
Route::post('/deposit/callback', [\App\Http\Controllers\DepositPaymentController::class, 'paymentCallback'])->name('deposit.callback');
Route::get('/deposit/status/{referenceCode}', [\App\Http\Controllers\DepositPaymentController::class, 'getPaymentStatus'])->name('deposit.status');

// Cash Purchase Routes
Route::get('/cash-purchase', [\App\Http\Controllers\CashPurchaseController::class, 'index'])->name('cash.purchase');
Route::get('/cash-purchase/success/{purchase}', [\App\Http\Controllers\CashPurchaseController::class, 'success'])->name('cash.purchase.success');
Route::get('/cash-purchase/error', [\App\Http\Controllers\CashPurchaseController::class, 'error'])->name('cash.purchase.error');

// Cross-platform synchronization routes
Route::post('/application/switch-to-whatsapp', [ApplicationWizardController::class, 'switchToWhatsApp'])->name('application.switch.whatsapp');
Route::post('/application/switch-to-web', [ApplicationWizardController::class, 'switchToWeb'])->name('application.switch.web');
Route::get('/application/sync-status', [ApplicationWizardController::class, 'getSyncStatus'])->name('application.sync.status');
Route::post('/application/synchronize', [ApplicationWizardController::class, 'synchronizeData'])->name('application.synchronize');

// PDF Routes
Route::get('/application/download/{sessionId}', [ApplicationPDFController::class, 'download'])->name('application.pdf.download');
Route::get('/application/view/{sessionId}', [ApplicationPDFController::class, 'view'])->name('application.pdf.view');
Route::post('/application/pdf/batch', [ApplicationPDFController::class, 'batchDownload'])->name('application.pdf.batch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // PDF Management Routes
    Route::prefix('admin/pdf')->name('admin.pdf.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PDFManagementController::class, 'index'])->name('index');
        Route::get('/download/{sessionId}', [\App\Http\Controllers\Admin\PDFManagementController::class, 'download'])->name('download');
        Route::post('/bulk-download', [\App\Http\Controllers\Admin\PDFManagementController::class, 'bulkDownload'])->name('bulk-download');
        Route::post('/export-bank', [\App\Http\Controllers\Admin\PDFManagementController::class, 'exportForBank'])->name('export-bank');
        Route::get('/statistics', [\App\Http\Controllers\Admin\PDFManagementController::class, 'statistics'])->name('statistics');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\PDFManagementController::class, 'cleanup'])->name('cleanup');
        Route::post('/regenerate/{sessionId}', [\App\Http\Controllers\Admin\PDFManagementController::class, 'regenerate'])->name('regenerate');
    });
    
    // Admin Export Routes
    Route::prefix('admin/export')->name('admin.export.')->group(function () {
        Route::get('/holiday-packages', [\App\Http\Controllers\AdminController::class, 'exportHolidayPackages'])->name('holiday-packages');
    });
    
    // Admin routes (Filament handles /admin routes)
    /*
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');

        // Application Management (TODO: Implement controllers)
        /*
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'index'])->name('index');
            Route::get('/{sessionId}', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'show'])->name('show');
            Route::put('/{sessionId}/status', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'updateStatus'])->name('update-status');
            Route::post('/{sessionId}/notes', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'addNote'])->name('add-note');
            Route::get('/{sessionId}/pdf', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'downloadPdf'])->name('download-pdf');
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'bulkAction'])->name('bulk-action');
        });
        */

        // Analytics & Reports (TODO: Implement controllers)
        /*
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
            Route::get('/channel-performance', [\App\Http\Controllers\Admin\AnalyticsController::class, 'channelPerformance'])->name('channel-performance');
            Route::get('/conversion-funnel', [\App\Http\Controllers\Admin\AnalyticsController::class, 'conversionFunnel'])->name('conversion-funnel');
        });
        */

        // System Management (TODO: Implement controllers)
        /*
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/health', [\App\Http\Controllers\Admin\SystemController::class, 'health'])->name('health');
            Route::get('/logs', [\App\Http\Controllers\Admin\SystemController::class, 'logs'])->name('logs');
            Route::post('/cache/clear', [\App\Http\Controllers\Admin\SystemController::class, 'clearCache'])->name('clear-cache');
            Route::get('/queue/status', [\App\Http\Controllers\Admin\SystemController::class, 'queueStatus'])->name('queue-status');
            Route::post('/maintenance', [\App\Http\Controllers\Admin\SystemController::class, 'toggleMaintenance'])->name('maintenance');
        });

        // User Management (TODO: Implement controllers)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::put('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
        });

        // Settings (TODO: Implement controllers)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
            Route::put('/general', [\App\Http\Controllers\Admin\SettingsController::class, 'updateGeneral'])->name('update-general');
            Route::put('/notifications', [\App\Http\Controllers\Admin\SettingsController::class, 'updateNotifications'])->name('update-notifications');
            Route::put('/security', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSecurity'])->name('update-security');
        });
        */
});

// Agent Portal Routes
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AgentPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AgentPortalController::class, 'login'])->name('login.submit');
    Route::get('/dashboard', [\App\Http\Controllers\AgentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/logout', [\App\Http\Controllers\AgentPortalController::class, 'logout'])->name('logout');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/test.php';

