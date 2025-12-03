<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\MaintenanceRequestController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\ReportsController;
use App\Http\Controllers\Tenant\PaymentController as TenantPaymentController;
use App\Http\Controllers\Tenant\TenantController;
use App\Http\Controllers\Tenant\TenantApplicationController;
use App\Http\Controllers\Tenant\TenantRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Manager\PropertyApplicationController;
use App\Http\Controllers\Manager\UnitController;
use App\Http\Controllers\Manager\UtilityController;
use App\Http\Controllers\Tenant\LeaseController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\TenantLeaseController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Route::get('/run-scheduler', function () {
    Artisan::call('schedule:run');
    return "Scheduler executed successfully!";
});

// Default route
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/test-mail', function() {
    try {
        Mail::raw('Test email content', function($message) {
            $message->to('jalix2003@gmail.com')
                    ->subject('Test Email');
        });
        return 'Email sent!';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});


// -----------------------------
// Auth routes
// -----------------------------
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', action: [RegisterController::class, 'index'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
Route::post('/register/check-email', [RegisterController::class, 'checkEmail'])->name('register.check-email');

// Forgot password (request reset link)
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])
    ->name('password.request');

Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');

// Reset password (via link in email)
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
     ->name('password.reset');

Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');


// -----------------------------
// Manager routes (only managers)
// -----------------------------
// Route::middleware(['auth', 'manager'])->group(function () {
//     Route::get('/manager/dashboard', [DashboardController::class, 'dashboard'])->name('manager.dashboard');
//     Route::get('/manager/reports', [DashboardController::class, 'reports'])->name('manager.reports');
//     Route::get('/manager/settings', [DashboardController::class, 'settings'])->name('manager.settings');
//     Route::get('/manager/tenants', [DashboardController::class, 'manageTenants'])->name('manager.tenants');

//     Route::post('/manager/approve/{id}', [ManagerController::class, 'approve'])->name('manager.approve');
//     Route::post('/manager/reject/{id}', [ManagerController::class, 'reject'])->name('manager.reject');
// });
// Route::middleware(['auth', 'manager'])->group(function () {
//     Route::get('/manager/dashboard', [DashboardController::class, 'dashboard'])->name('manager.dashboard');
//     Route::get('/manager/settings', [DashboardController::class, 'settings'])->name('manager.settings');
//     Route::get('/manager/tenants', [DashboardController::class, 'manageTenants'])->name('manager.tenants');

//     // Reports
//     Route::get('/manager/reports', [ReportsController::class, 'index'])->name('manager.reports');
//     Route::get('/manager/reports/{report}', [ReportsController::class, 'show'])->name('manager.reports.show');
//     Route::get('manager/reports/{report}/export', [ReportsController::class, 'export'])
//     ->name('manager.reports.export');
//     Route::patch('/manager/payments/{payment}/status', [ReportsController::class, 'updatePaymentStatus'])
//     ->name('manager.payments.updateStatus');

//     // Requests
//     Route::patch('/manager/requests/{id}/status', [App\Http\Controllers\Manager\MaintenanceRequestController::class, 'updateStatus'])
//     ->name('manager.requests.updateStatus');
//     Route::get('/manager/requests/{id}', [App\Http\Controllers\Manager\MaintenanceRequestController::class, 'show'])
//         ->name('manager.requests.show');

//     // Tenant approval actions
//     Route::post('/manager/approve/{id}', [ManagerController::class, 'approve'])->name('manager.approve');
//     Route::post('/manager/reject/{id}', [ManagerController::class, 'reject'])->name('manager.reject');

//     // Tenant Unit Controller
//     // Units Management
//     // Manager Unit Routes
//     Route::get('/manager/units', [App\Http\Controllers\Manager\UnitController::class, 'index'])
//         ->name('manager.units.index');

//     Route::post('/manager/units', [App\Http\Controllers\Manager\UnitController::class, 'store'])
//         ->name('manager.units.store');

//     Route::get('/manager/units/{unit}/edit', [App\Http\Controllers\Manager\UnitController::class, 'edit'])
//         ->name('manager.units.edit');

//     Route::put('/manager/units/{unit}', [App\Http\Controllers\Manager\UnitController::class, 'update'])
//         ->name('manager.units.update');

//     Route::delete('/manager/units/{unit}', [App\Http\Controllers\Manager\UnitController::class, 'destroy'])
//         ->name('manager.units.destroy');

// });


Route::middleware(['auth', 'manager'])->group(function () {
    // Dashboard & Settings
    Route::get('/manager/dashboard', [DashboardController::class, 'dashboard'])->name('manager.dashboard');
    Route::get('/manager/settings', [DashboardController::class, 'settings'])->name('manager.settings');
    Route::get('/manager/tenants', [DashboardController::class, 'manageTenants'])->name('manager.tenants');

    // Reports
    Route::get('/manager/reports', [ReportsController::class, 'index'])->name('manager.reports');
    Route::get('/manager/reports/{report}', [ReportsController::class, 'show'])->name('manager.reports.show');
    Route::get('/manager/reports/{report}/export', [ReportsController::class, 'export'])->name('manager.reports.export');
    Route::patch('/manager/payments/{payment}/status', [ReportsController::class, 'updatePaymentStatus'])->name('manager.payments.updateStatus');

    // Reports export
    // Route::get('/manager/reports/{report}/export', [ReportsController::class, 'export'])->name('manager.reports.export');

    // New page for viewing/exporting reports
    Route::get('/manager/reports/export/{report}', [ReportsController::class, 'viewReportPdf'])
    ->name('manager.reports.viewReportPdf');

    // New page for id viewing
    Route::get('/manager/tenants/{id}/view-ids', [ManagerController::class, 'viewIds'])
    ->name('manager.tenants.viewIds');

    Route::get('/manager/tenants/export', [ManagerController::class, 'exportTenantsPdf'])
    ->name('manager.tenants.export');

    // manual notification to tenant
    Route::post('/manager/tenants/notify/{id}', [ManagerController::class, 'notifyTenant'])
    ->name('manager.tenants.notify');


    // Requests
    Route::patch('/manager/requests/{id}/status', [MaintenanceRequestController::class, 'updateStatus'])->name('manager.requests.updateStatus');
    Route::get('/manager/requests/{id}', [MaintenanceRequestController::class, 'show'])->name('manager.requests.show');

    // Tenant approval actions
    Route::get('/manager/tenants', [ManagerController::class, 'tenants'])->name('manager.tenants');
    Route::get('/manager/tenants/filter', [ManagerController::class, 'filterTenants'])->name('manager.tenants.filter');


    Route::post('/manager/tenant/approve/{id}', [ManagerController::class, 'approve'])->name('manager.tenant.approve');
    Route::post('/manager/tenant/reject', [ManagerController::class, 'reject'])
        ->name('manager.tenant.reject');

    Route::post('/manager/leases/{lease}/move-out', [ManagerController::class, 'forceMoveOut'])
        ->name('manager.leases.moveOut');

    // Units Management
    Route::get('/manager/units', [UnitController::class, 'index'])->name('manager.units.index');
    Route::post('/manager/units', [UnitController::class, 'store'])->name('manager.units.store');

    // Units approval for additional units
    Route::post('/manager/approve-unit/{user}/{unit}/{lease}', [UnitController::class, 'approveAdditionalUnit'])
    ->name('manager.approve-unit');

    // Reject additional unit application
    Route::post('/manager/reject-unit/{user}/{unit}/{lease}', [UnitController::class, 'rejectAdditionalUnit'])
        ->name('manager.reject-unit');



    Route::get('/manager/units/{unit}/edit', [UnitController::class, 'edit'])->name('manager.units.edit');
    Route::put('/manager/units/{unit}', [UnitController::class, 'update'])->name('manager.units.update');
    Route::delete('/manager/units/{unit}', [UnitController::class, 'destroy'])->name('manager.units.destroy');
    // Utility Manager
    Route::get('/manager/utilities', [App\Http\Controllers\Manager\UtilityController::class, 'index'])
        ->name('manager.utilities.index');

    Route::put('/manager/utilities/{id}', [UtilityController::class, 'updateUtilityBalance'])
        ->name('manager.utilities.update');
});


// Tenant application form (modal on dashboard)
Route::middleware(['auth'])->group(function () {
    Route::get('/tenant/application', [TenantApplicationController::class, 'show'])
        ->name('tenant.application');
    Route::post('/tenant/application/submit', [TenantApplicationController::class, 'submit'])
        ->name('tenant.application.submit');
});

// Tenant dashboard/routes
Route::middleware(['auth'])->group(function () {
    Route::get('/tenant/home', [TenantController::class, 'dashboard'])->name('tenant.home');
    Route::get('/tenant/payments', [TenantController::class, 'payments'])->name('tenant.payments');
    Route::get('/tenant/requests', [TenantController::class, 'requests'])->name('tenant.requests');

    // Tenant application submission (modal on dashboard)
    Route::post('/tenant/application/submit', [TenantApplicationController::class, 'submit'])
        ->name('tenant.application.submit');

    Route::get('/tenant/payments', [TenantPaymentController::class, 'index'])
        ->name('tenant.payments');

    // Tenant make payment
    Route::post('/tenant/payments/store', [TenantPaymentController::class, 'store'])
        ->name('tenant.payments.store');

    Route::get('/tenant/requests', [TenantRequestController::class, 'index'])
        ->name('tenant.requests');
    Route::post('/tenant/requests/store', [TenantRequestController::class, 'store'])
        ->name('tenant.requests.store');
    Route::patch('/tenant/requests/{id}/cancel', [TenantRequestController::class, 'cancel'])
    ->name('tenant.requests.cancel');


    Route::get('/tenant/notifications', [NotificationController::class, 'index'])
    ->name('tenant.notifications');

    Route::get('/tenant/leases', [TenantLeaseController::class, 'index'])
    ->name('tenant.leases')
    ->middleware('auth');

    Route::post('/tenant/leases/apply', [TenantLeaseController::class, 'store'])
        ->name('tenant.leases.store')
        ->middleware('auth');

    Route::post('/tenant/leases/{id}/request-move-out', [TenantLeaseController::class, 'requestMoveOut'])
        ->name('tenant.leases.requestMoveOut')
        ->middleware('auth');


});


Route::get('/clear-mail-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return "MAIL SETTINGS REFRESHED";
});

