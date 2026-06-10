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


// Application resume helpers — registered under the web group so the session is
// available and Auth::id() resolves for the logged-in client (the api group is stateless).
Route::get('/application/resume-state', [\App\Http\Controllers\Api\StateController::class, 'currentUserState'])->name('application.current_state');
Route::post('/application/link-user', [\App\Http\Controllers\Api\StateController::class, 'linkUserToState'])->name('application.link_user');

// Application Wizard Routes
Route::get('/application', [ApplicationWizardController::class, 'show'])->name('application.wizard');
Route::get('/apply', [ApplicationWizardController::class, 'showWithReferral'])->name('application.apply'); // Agent referral entry point
Route::get('/application/resume/{identifier}', [ApplicationWizardController::class, 'resume'])->name('application.resume');
Route::get('/application/status', [ApplicationWizardController::class, 'status'])->name('application.status');
Route::get('/application/success', [ApplicationWizardController::class, 'success'])->name('application.success');
Route::post('/application/convert-account', [ApplicationWizardController::class, 'convertAccountToApplication'])->name('application.convert_account');
Route::get('/delivery/tracking', [ApplicationWizardController::class, 'tracking'])->name('delivery.tracking');
Route::get('/reference-code', [ApplicationWizardController::class, 'referenceCodeLookup'])->name('reference.code.lookup');

// Deposit Payment Routes
Route::post('/deposit/initiate', [\App\Http\Controllers\DepositPaymentController::class, 'initiatePayment'])->name('deposit.initiate');
Route::post('/deposit/callback', [\App\Http\Controllers\DepositPaymentController::class, 'paymentCallback'])->name('deposit.callback');
Route::get('/deposit/status/{referenceCode}', [\App\Http\Controllers\DepositPaymentController::class, 'getPaymentStatus'])->name('deposit.status');


// Cross-platform synchronization routes
Route::post('/application/switch-to-whatsapp', [ApplicationWizardController::class, 'switchToWhatsApp'])->name('application.switch.whatsapp');
Route::post('/application/switch-to-web', [ApplicationWizardController::class, 'switchToWeb'])->name('application.switch.web');
Route::get('/application/sync-status', [ApplicationWizardController::class, 'getSyncStatus'])->name('application.sync.status');
Route::post('/application/synchronize', [ApplicationWizardController::class, 'synchronizeData'])->name('application.synchronize');

// PDF Routes
Route::get('/application/download/{sessionId}', [ApplicationPDFController::class, 'download'])->name('application.pdf.download');
Route::get('/application/view/{sessionId}', [ApplicationPDFController::class, 'view'])->name('application.pdf.view');
Route::get('/application/{sessionId}/pdf/download', [ApplicationPDFController::class, 'legacyDownload'])->name('application.pdf.download.legacy');
Route::get('/application/{sessionId}/pdf/view', [ApplicationPDFController::class, 'legacyView'])->name('application.pdf.view.legacy');
Route::post('/application/{sessionId}/pdf/regenerate', [ApplicationPDFController::class, 'regenerate'])->name('application.pdf.regenerate.legacy');
Route::get('/application/receipt/download/{sessionId}', [ApplicationPDFController::class, 'downloadReceipt'])->name('application.receipt.download');
Route::post('/application/pdf/batch', [ApplicationPDFController::class, 'batchDownload'])->name('application.pdf.batch');
Route::post('/application/pdf/batch-download', [ApplicationPDFController::class, 'legacyBatchDownload'])->name('application.pdf.batch.legacy');

// Account Opening PDF Routes
Route::get('/account-opening/pdf/{id}', [ApplicationPDFController::class, 'downloadAccountOpening'])->name('account-opening.pdf.download');

// Agent ID image proxy — serves Twilio/Cloud API images through server-side auth
Route::get('/admin/agent-media/{agent}/{side}', [\App\Http\Controllers\AgentMediaController::class, 'show'])
    ->where('side', 'front|back')
    ->name('admin.agent.media');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = Auth::user();
        
        if ($user) {
            if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN) {
                return redirect()->intended('/admin');
            }
            if ($user->role === \App\Models\User::ROLE_ZB_ADMIN) {
                return redirect()->intended('/zb-admin');
            }
            if ($user->role === \App\Models\User::ROLE_QUPA_ADMIN) {
                return redirect()->intended('/zb-admin');
            }
            if ($user->role === \App\Models\User::ROLE_ACCOUNTING) {
                return redirect()->intended('/accounting');
            }
            if ($user->role === \App\Models\User::ROLE_STORES) {
                return redirect()->intended('/stores');
            }
            if ($user->role === \App\Models\User::ROLE_HR) {
                return redirect()->intended('/hr');
            }
            if ($user->role === \App\Models\User::ROLE_PARTNER) {
                return redirect()->intended('/partner');
            }
        }

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
    
    // Admin UI is served by Filament under /admin (see app/Filament).
});

// Agent Portal Routes
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AgentPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AgentPortalController::class, 'login'])->name('login.submit');
    Route::get('/dashboard', [\App\Http\Controllers\AgentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/deliveries/zimpost', [\App\Http\Controllers\AgentPortalController::class, 'zimpostDeliveries'])->name('zimpost.deliveries');
    Route::get('/logout', [\App\Http\Controllers\AgentPortalController::class, 'logout'])->name('logout');
    Route::post('/generate-product-link', [\App\Http\Controllers\AgentPortalController::class, 'generateProductLink'])->name('generate.product.link');
    Route::post('/log-activity', [\App\Http\Controllers\AgentPortalController::class, 'logActivity'])->name('log.activity');
    Route::post('/reactivate', [\App\Http\Controllers\AgentPortalController::class, 'reactivate'])->name('reactivate');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// SECURITY: Only load test routes in local/development environment
if (app()->environment('local', 'development', 'testing')) {
    require __DIR__.'/test.php';
}
