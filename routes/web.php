<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

    //rota para cadastro
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
    Route::get('/register/pending', [AuthController::class, 'showPending'])->name('register.pending');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password',        [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
    //verificação
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');

    

Route::middleware(['auth', 'aba.perm'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class);

    //Rota Validades
    Route::get('validades',            [BatchController::class, 'index'])->name('batches.index');
    Route::post('validades',           [BatchController::class, 'store'])->name('batches.store');
    Route::put('validades/{batch}',    [BatchController::class, 'update'])->name('batches.update');
    Route::delete('validades/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

    //rota categorias
    Route::resource('categories', CategoryController::class)
    ->only(['index', 'store', 'update', 'destroy']);

    //rota ordem_compra
    Route::resource('purchase-orders', PurchaseOrderController::class)
    ->only(['index','create','store','show','destroy']);
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])
    ->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
    ->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/reject',  [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::get('purchase-orders/{purchase_order}/report',   [PurchaseOrderController::class, 'reportPdf'])->name('purchase-orders.report');

    //rota fornecedores
    Route::resource('suppliers', SupplierController::class)
    ->only(['index','store','update','destroy']);

    //rota relatórios]
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('reports/low-stock', [ReportController::class, 'lowStockPdf'])->name('reports.lowstock');

    //rota movimentos
    Route::get('movements/export', [MovementController::class, 'export'])->name('movements.export');
    Route::get('movements/{movement}/document',  [MovementController::class, 'document'])->name('movements.document');
    Route::get('reports/returns',                [ReportController::class, 'returns'])->name('reports.returns');
    Route::get('/movements', [MovementController::class, 'index'])->name('movements.index');
    Route::post('/movements', [MovementController::class, 'store'])->name('movements.store');
    Route::post('movements/{movement}/reverse',  [MovementController::class, 'reverse'])->name('movements.reverse');
    Route::post('movements/{movement}/approve', [MovementController::class, 'approve'])->name('movements.approve');
    Route::post('movements/{movement}/reject',  [MovementController::class, 'reject'])->name('movements.reject');

    //rotas para perfil
    Route::get('profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile',        [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('profile/employees',              [ProfileController::class, 'storeEmployee'])->name('profile.employees.store');
    Route::put('profile/employees/{employee}',    [ProfileController::class, 'updateEmployee'])->name('profile.employees.update');
    Route::delete('profile/employees/{employee}', [ProfileController::class, 'destroyEmployee'])->name('profile.employees.destroy');
    Route::post('profile/notifications/read', [ProfileController::class, 'markNotificationsRead'])->name('profile.notifications.read');
    Route::post('profile/signups/{user}/approve', [ProfileController::class, 'approveSignup'])->name('profile.signups.approve');
    Route::post('profile/signups/{user}/reject',  [ProfileController::class, 'rejectSignup'])->name('profile.signups.reject');
    Route::post('profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');

    //finanças
    Route::middleware('auth')->prefix('finance')->name('finance.')->group(function () {
    Route::get('/',           [FinanceController::class, 'index'])->name('index');
    Route::get('/cash-flow',  [FinanceController::class, 'cashFlow'])->name('cashflow');

    Route::get('/payables',                  [FinanceController::class, 'payables'])->name('payables');
    Route::post('/payables',                 [FinanceController::class, 'storePayable'])->name('payables.store');
    Route::patch('/payables/{payable}/pay',  [FinanceController::class, 'markPaid'])->name('payables.pay');
    Route::delete('/payables/{payable}',     [FinanceController::class, 'destroyPayable'])->name('payables.destroy');

    Route::get('/receivables',                        [FinanceController::class, 'receivables'])->name('receivables');
    Route::post('/receivables',                       [FinanceController::class, 'storeReceivable'])->name('receivables.store');
    Route::patch('/receivables/{receivable}/receive', [FinanceController::class, 'markReceived'])->name('receivables.receive');
    Route::delete('/receivables/{receivable}',        [FinanceController::class, 'destroyReceivable'])->name('receivables.destroy');
});
});