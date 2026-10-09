<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\IncomeEntryController;
use App\Http\Controllers\Finance\InstallmentController;
use App\Http\Controllers\Finance\ServiceController;
use App\Http\Controllers\Finance\ServicePaymentController;
use App\Http\Controllers\Finance\SettlementController;
use App\Http\Controllers\Finance\SmartSuggestionController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Notifications\PushDeviceController;
use App\Http\Controllers\Reports\DashboardController;
use App\Http\Controllers\Reports\HealthScoreController;
use App\Http\Controllers\Reports\MonthlyClosingController;
use App\Http\Controllers\Reports\WorkspaceExportController;
use App\Http\Controllers\Savings\SavingsGoalController;
use App\Http\Controllers\Savings\SavingsWalletController;
use App\Http\Controllers\Sidebar\SidebarSectionController;
use App\Http\Controllers\Workspace\ActivityController;
use App\Http\Controllers\Workspace\InvitationController;
use App\Http\Controllers\Workspace\WorkspaceController;
use App\Http\Controllers\Workspace\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', MetricsController::class);

Route::prefix('auth')->group(function () {
    // Públicas
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth-refresh');
    Route::post('/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:auth-forgot');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:auth-forgot');

    // Challenge de 2FA (token intermedio, no es una sesión completa)
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])
        ->middleware(['auth:sanctum', 'ability:2fa:verify', 'throttle:auth-2fa']);

    // Requieren sesión completa (access token)
    Route::middleware(['auth:sanctum', 'ability:access'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/password', [PasswordController::class, 'change'])->middleware('throttle:auth-password');

        Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:auth-2fa');
        Route::delete('/2fa', [TwoFactorController::class, 'disable']);
    });
});

Route::middleware(['auth:sanctum', 'ability:access'])->group(function () {
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index']);
        Route::post('/', [WorkspaceController::class, 'store']);

        Route::prefix('{workspaceId}')->middleware('workspace.member')->group(function () {
            Route::get('/', [WorkspaceController::class, 'show']);
            Route::get('/members', [WorkspaceMemberController::class, 'index']);
            Route::post('/leave', [WorkspaceMemberController::class, 'leave']);
            Route::post('/onboarding/complete', [WorkspaceMemberController::class, 'completeOnboarding']);
            Route::get('/activity', [ActivityController::class, 'index']);
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/dashboard/history', [DashboardController::class, 'history']);
            Route::get('/health-scores', [HealthScoreController::class, 'index']);
            Route::get('/export', [WorkspaceExportController::class, 'export']);

            Route::get('/closings', [MonthlyClosingController::class, 'index']);
            Route::get('/closings/{year}/{month}', [MonthlyClosingController::class, 'show']);
            Route::post('/closings/{year}/{month}/allocate', [MonthlyClosingController::class, 'allocate']);

            Route::get('/suggestions', [SmartSuggestionController::class, 'index']);
            Route::post('/suggestions/{suggestionId}/accept', [SmartSuggestionController::class, 'accept']);
            Route::post('/suggestions/{suggestionId}/dismiss', [SmartSuggestionController::class, 'dismiss']);

            Route::get('/sidebar-sections', [SidebarSectionController::class, 'index']);
            Route::put('/sidebar-sections', [SidebarSectionController::class, 'update']);

            Route::get('/categories', [CategoryController::class, 'index']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{categoryId}', [CategoryController::class, 'update']);
            Route::delete('/categories/{categoryId}', [CategoryController::class, 'destroy']);

            Route::get('/budgets', [BudgetController::class, 'index']);
            Route::put('/budgets', [BudgetController::class, 'upsert']);
            Route::post('/budgets/copy', [BudgetController::class, 'copy']);
            Route::delete('/budgets/{budgetId}', [BudgetController::class, 'destroy']);

            Route::get('/income-entries', [IncomeEntryController::class, 'index']);
            Route::post('/income-entries', [IncomeEntryController::class, 'store']);
            Route::put('/income-entries/{incomeEntryId}', [IncomeEntryController::class, 'update']);
            Route::delete('/income-entries/{incomeEntryId}', [IncomeEntryController::class, 'destroy']);

            Route::get('/expenses', [ExpenseController::class, 'index']);
            Route::post('/expenses', [ExpenseController::class, 'store']);
            // Antes que /expenses/{expenseId} para que "payment-methods" no se interprete como un id.
            Route::get('/expenses/payment-methods', [ExpenseController::class, 'paymentMethods']);
            Route::get('/expenses/{expenseId}', [ExpenseController::class, 'show']);
            Route::put('/expenses/{expenseId}', [ExpenseController::class, 'update']);
            Route::delete('/expenses/{expenseId}', [ExpenseController::class, 'destroy']);

            Route::get('/services', [ServiceController::class, 'index']);
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{serviceId}', [ServiceController::class, 'update']);
            Route::delete('/services/{serviceId}', [ServiceController::class, 'destroy']);

            Route::get('/service-payments', [ServicePaymentController::class, 'index']);
            // Antes que /service-payments/{servicePaymentId}/... para que "overdue" no se interprete como un id.
            Route::get('/service-payments/overdue', [ServicePaymentController::class, 'overdue']);
            Route::post('/service-payments/{servicePaymentId}/pay', [ServicePaymentController::class, 'pay']);
            Route::post('/service-payments/{servicePaymentId}/unpay', [ServicePaymentController::class, 'unpay']);

            Route::get('/installments', [InstallmentController::class, 'index']);
            Route::post('/installments', [InstallmentController::class, 'store']);
            Route::get('/installments/{installmentId}', [InstallmentController::class, 'show']);
            Route::put('/installments/{installmentId}', [InstallmentController::class, 'update']);
            Route::delete('/installments/{installmentId}', [InstallmentController::class, 'destroy']);
            Route::post(
                '/installments/{installmentId}/payments/{paymentId}/pay',
                [InstallmentController::class, 'pay'],
            );
            Route::post(
                '/installments/{installmentId}/payments/{paymentId}/unpay',
                [InstallmentController::class, 'unpay'],
            );

            Route::get('/savings/wallet', [SavingsWalletController::class, 'wallet']);
            Route::get('/savings/wallet/history', [SavingsWalletController::class, 'history']);
            Route::get('/savings/movements', [SavingsWalletController::class, 'movements']);
            Route::post('/savings/movements', [SavingsWalletController::class, 'storeMovement']);

            Route::get('/savings/goals', [SavingsGoalController::class, 'index']);
            Route::post('/savings/goals', [SavingsGoalController::class, 'store']);
            Route::put('/savings/goals/{goalId}', [SavingsGoalController::class, 'update']);
            Route::delete('/savings/goals/{goalId}', [SavingsGoalController::class, 'destroy']);
            Route::post('/savings/goals/{goalId}/contribute', [SavingsGoalController::class, 'contribute']);
            Route::get('/savings/goals/{goalId}/movements', [SavingsGoalController::class, 'movements']);
            Route::post('/savings/goals/{goalId}/transfer-from-wallet', [SavingsGoalController::class, 'transfer']);

            Route::get('/settlement', [SettlementController::class, 'index']);
            Route::post('/settlement-payments', [SettlementController::class, 'storePayment']);
            Route::delete(
                '/settlement-payments/{settlementPaymentId}',
                [SettlementController::class, 'destroyPayment'],
            );

            Route::middleware('workspace.owner')->group(function () {
                Route::put('/', [WorkspaceController::class, 'update']);
                Route::delete('/', [WorkspaceController::class, 'destroy']);
                Route::delete('/members/{userId}', [WorkspaceMemberController::class, 'destroy']);
                Route::get('/invitations', [InvitationController::class, 'index']);
                Route::post('/invitations', [InvitationController::class, 'store'])->middleware('throttle:invitations');
                Route::delete('/invitations/{invitationId}', [InvitationController::class, 'destroy']);
            });
        });
    });

    // Antes que /invitations/{code} para que "mine" no se interprete como un código.
    Route::get('/invitations/mine', [InvitationController::class, 'mine']);
    Route::post('/invitations/{code}/accept', [InvitationController::class, 'accept']);

    // Antes que /notifications/{notificationId}/read para que "read-all" no se interprete como un id.
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead']);
    Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
    Route::post('/push-devices', [PushDeviceController::class, 'store']);
    Route::delete('/push-devices', [PushDeviceController::class, 'destroy']);
});

Route::get('/invitations/{code}', [InvitationController::class, 'preview'])->middleware('throttle:invitations-public');
