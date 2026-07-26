<?php

use App\Http\Controllers\Admin\AdminAdminController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\AdminAssetController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBotController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDepositController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminHealthController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSystemSettingsController;
use App\Http\Controllers\Admin\AdminTradeController;
use App\Http\Controllers\Admin\AdminTradingEngineController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ModalController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PalPlussWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

// Landing page — trading terminal for authenticated users, marketing page for guests
Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard — redirect to the trading terminal
    Route::get('/dashboard', fn() => redirect()->route('trade.index'))->name('dashboard');

    // Trade — page
    Route::get('/trade', [TradeController::class, 'index'])->name('trade.index');

    // Trade — JSON endpoints
    Route::post('/trade/place',                   [TradeController::class, 'place'])->name('trade.place')->middleware('throttle:trade');
    Route::get('/trade/active',                   [TradeController::class, 'active'])->name('trade.active');
    Route::get('/trade/recent',                   [TradeController::class, 'recent'])->name('trade.recent');
    Route::get('/trade/market-snapshot',          [MarketController::class, 'snapshot'])->name('trade.market');
    Route::get('/trade/assets/{asset}/ticks',     [MarketController::class, 'ticks'])->name('trade.ticks');

    // Modal endpoints (AJAX HTML fragments)
    Route::get('/modal/deposit',   [ModalController::class, 'deposit'])->name('modal.deposit');
    Route::get('/modal/withdraw',  [ModalController::class, 'withdraw'])->name('modal.withdraw');
    Route::get('/modal/wallet',    [ModalController::class, 'wallet'])->name('modal.wallet');
    Route::get('/modal/history',   [ModalController::class, 'history'])->name('modal.history');
    Route::get('/modal/bots',          [ModalController::class, 'bots'])->name('modal.bots');
    Route::get('/modal/notifications', [ModalController::class, 'notifications'])->name('modal.notifications');
    Route::get('/modal/profile',       [ModalController::class, 'profile'])->name('modal.profile');
    Route::get('/modal/settings',  [ModalController::class, 'settings'])->name('modal.settings');
    Route::get('/modal/logout',    [ModalController::class, 'logout'])->name('modal.logout');
    Route::get('/modal/referral',  [ModalController::class, 'referral'])->name('modal.referral');
    Route::get('/modal/support',   [ModalController::class, 'support'])->name('modal.support');

    // Bots (index and earnings redirect to trade page where modal can be opened)
    Route::get('/bots',                                    fn() => redirect()->route('trade.index'))->name('bots.index');
    Route::post('/bots/invest',                            [BotController::class, 'invest'])->name('bots.invest')->middleware('throttle:bot-invest');
    Route::post('/bots/investments/{investment}/cancel',   [BotController::class, 'cancel'])->name('bots.cancel');
    Route::get('/bots/earnings',                           [BotController::class, 'earnings'])->name('bots.earnings');

    // Wallet (index and deposit redirect to trade page)
    Route::get('/wallet',              fn() => redirect()->route('trade.index'))->name('wallet.index');
    Route::get('/wallet/deposit',      fn() => redirect()->route('trade.index'))->name('wallet.deposit');
    Route::post('/wallet/mode',        [WalletController::class, 'switchMode'])->name('wallet.mode');
    Route::post('/wallet/demo/reset',  [WalletController::class, 'resetDemo'])->name('wallet.demo.reset');

    // Withdrawals (index redirects to trade page)
    Route::get('/wallet/withdraw',          fn() => redirect()->route('trade.index'))->name('wallet.withdraw');
    Route::post('/wallet/withdraw/mpesa',   [WithdrawalController::class, 'requestMpesa'])->name('withdrawals.mpesa')->middleware('throttle:withdrawal');
    Route::post('/wallet/withdraw/usdt',    [WithdrawalController::class, 'requestUsdt'])->name('withdrawals.usdt')->middleware('throttle:withdrawal');
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');

    // Transactions (redirects to trade page)
    Route::get('/transactions', fn() => redirect()->route('trade.index'))->name('transactions.index');

    // Deposits
    Route::post('/wallet/deposit/mpesa',              [DepositController::class, 'initiateMpesa'])->name('deposits.mpesa')->middleware('throttle:deposit');
    Route::post('/wallet/deposit/usdt',               [DepositController::class, 'storeUsdtDeposit'])->name('deposits.usdt')->middleware('throttle:deposit');
    Route::get('/deposits/{deposit}',                 [DepositController::class, 'show'])->name('deposits.show');
    Route::post('/deposits/{deposit}/refresh-status', [DepositController::class, 'refreshStatus'])->name('deposits.refresh')->middleware('throttle:deposit-refresh');

    // Profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings.index');
    Route::patch('/user/theme', function(\Illuminate\Http\Request $request) {
        $request->validate(['theme' => 'required|in:dark,light,system']);
        auth()->user()->update(['theme_preference' => $request->theme]);
        return response()->json(['success' => true]);
    })->name('user.theme');

    // Support
    Route::get('/support', [DashboardController::class, 'support'])->name('support.index');

    // Notifications
    Route::get('/notifications',                               [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read',          [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',                     [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// ─── Admin Routes ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard (super admin only)
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // ── Users ──────────────────────────────────────────────────────────────
    Route::middleware('permission:view_users')->group(function () {
        Route::get('/users',              [AdminUserController::class, 'index'])->name('users');
        Route::get('/users/{user}',       [AdminUserController::class, 'show'])->name('users.show');
    });
    Route::post('/users/{user}/ban',              [AdminUserController::class, 'ban'])->middleware('permission:ban_users')->name('users.ban');
    Route::post('/users/{user}/unban',            [AdminUserController::class, 'unban'])->middleware('permission:ban_users')->name('users.unban');
    Route::post('/users/{user}/make-admin',       [AdminUserController::class, 'makeAdmin'])->middleware('permission:manage_admins')->name('users.make-admin');
    Route::post('/users/{user}/remove-admin',     [AdminUserController::class, 'removeAdmin'])->middleware('permission:manage_admins')->name('users.remove-admin');
    Route::post('/users/{user}/freeze-wallets',   [AdminUserController::class, 'freezeWallets'])->middleware('permission:freeze_wallets')->name('users.freeze-wallets');
    Route::post('/users/{user}/unfreeze-wallets', [AdminUserController::class, 'unfreezeWallets'])->middleware('permission:freeze_wallets')->name('users.unfreeze-wallets');

    // ── Roles ──────────────────────────────────────────────────────────────
    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/roles',                          [AdminRoleController::class, 'index'])->name('roles');
        Route::post('/roles',                         [AdminRoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}',                   [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}',                [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/{role}/permissions',      [AdminRoleController::class, 'syncPermissions'])->name('roles.permissions');
    });

    // ── Permissions ────────────────────────────────────────────────────────
    Route::middleware('permission:manage_permissions')->group(function () {
        Route::get('/permissions',                    [AdminPermissionController::class, 'index'])->name('permissions');
        Route::post('/permissions',                   [AdminPermissionController::class, 'store'])->name('permissions.store');
        Route::put('/permissions/{permission}',       [AdminPermissionController::class, 'update'])->name('permissions.update');
    });

    // ── Admin management ───────────────────────────────────────────────────
    Route::middleware('permission:manage_admins')->group(function () {
        Route::get('/admins',                         [AdminAdminController::class, 'index'])->name('admins');
        Route::post('/admins/{user}/roles',           [AdminAdminController::class, 'assignRole'])->name('admins.assign-role');
        Route::delete('/admins/{user}/roles/{role}',  [AdminAdminController::class, 'removeRole'])->name('admins.remove-role');
        Route::post('/admins/{user}/promote',         [AdminAdminController::class, 'promote'])->name('admins.promote');
        Route::post('/admins/{user}/demote',          [AdminAdminController::class, 'demote'])->name('admins.demote');
    });

    // ── Audit logs ─────────────────────────────────────────────────────────
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->middleware('permission:view_audit_logs')->name('audit-logs');

    // ── System settings ────────────────────────────────────────────────────
    Route::middleware('permission:manage_system_settings')->group(function () {
        Route::get('/system-settings',  [AdminSystemSettingsController::class, 'index'])->name('system-settings');
        Route::post('/system-settings', [AdminSystemSettingsController::class, 'update'])->name('system-settings.update');
    });

    // ── Trading engine ─────────────────────────────────────────────────────
    Route::middleware('permission:view_trading_engine')->group(function () {
        Route::get('/trading-engine', [AdminTradingEngineController::class, 'index'])->name('trading-engine');
        Route::get('/trades',         [AdminTradeController::class, 'index'])->name('trades');
    });
    Route::middleware('permission:manage_trading_engine')->group(function () {
        Route::post('/trading-engine/settings',                    [AdminTradingEngineController::class, 'updateSettings'])->name('trading-engine.settings');
        Route::post('/trading-engine/activate/{simulationSetting}',[AdminTradingEngineController::class, 'activate'])->name('trading-engine.activate');
        Route::post('/trading-engine/reset-defaults',              [AdminTradingEngineController::class, 'resetDefaults'])->name('trading-engine.reset');
    });

    // ── Assets ─────────────────────────────────────────────────────────────
    Route::middleware('permission:manage_assets')->group(function () {
        Route::get('/assets',                  [AdminAssetController::class, 'index'])->name('assets');
        Route::post('/assets',                 [AdminAssetController::class, 'store'])->name('assets.store');
        Route::put('/assets/{asset}',          [AdminAssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}',       [AdminAssetController::class, 'destroy'])->name('assets.destroy');
        Route::patch('/assets/{asset}/toggle', [AdminAssetController::class, 'toggle'])->name('assets.toggle');
    });

    // ── Bots ───────────────────────────────────────────────────────────────
    Route::middleware('permission:view_bots')->group(function () {
        Route::get('/bots', [AdminBotController::class, 'index'])->name('bots');
    });
    Route::middleware('permission:manage_bots')->group(function () {
        Route::post('/bots',                     [AdminBotController::class, 'store'])->name('bots.store');
        Route::put('/bots/{botPlan}',            [AdminBotController::class, 'update'])->name('bots.update');
        Route::delete('/bots/{botPlan}',         [AdminBotController::class, 'destroy'])->name('bots.destroy');
        Route::patch('/bots/{botPlan}/toggle',   [AdminBotController::class, 'toggle'])->name('bots.toggle');
    });

    // ── Deposits ───────────────────────────────────────────────────────────
    Route::middleware('permission:view_deposits')->group(function () {
        Route::get('/deposits',            [AdminDepositController::class, 'index'])->name('deposits');
        Route::get('/deposits/{deposit}',  [AdminDepositController::class, 'show'])->name('deposits.show');
    });
    Route::middleware('permission:manage_deposits')->group(function () {
        Route::post('/deposits/{deposit}/approve-mpesa', [AdminDepositController::class, 'approveMpesa'])->name('deposits.approve-mpesa');
        Route::post('/deposits/{deposit}/approve-usdt',  [AdminDepositController::class, 'approveUsdt'])->name('deposits.approve-usdt');
        Route::post('/deposits/{deposit}/reject-usdt',   [AdminDepositController::class, 'rejectUsdt'])->name('deposits.reject-usdt');
    });

    // ── Withdrawals ────────────────────────────────────────────────────────
    Route::middleware('permission:view_withdrawals')->group(function () {
        Route::get('/withdrawals',             [AdminWithdrawalController::class, 'index'])->name('withdrawals');
        Route::get('/withdrawals/{withdrawal}',[AdminWithdrawalController::class, 'show'])->name('withdrawals.show');
    });
    Route::middleware('permission:manage_withdrawals')->group(function () {
        Route::post('/withdrawals/{withdrawal}/approve',    [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject',     [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
        Route::post('/withdrawals/{withdrawal}/processing', [AdminWithdrawalController::class, 'processing'])->name('withdrawals.processing');
        Route::post('/withdrawals/{withdrawal}/successful', [AdminWithdrawalController::class, 'successful'])->name('withdrawals.successful');
        Route::post('/withdrawals/{withdrawal}/failed',     [AdminWithdrawalController::class, 'failed'])->name('withdrawals.failed');
    });

    // ── Referrals ──────────────────────────────────────────────────────────
    Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals');

    // ── System Health ──────────────────────────────────────────────────────
    Route::get('/system-health', [AdminHealthController::class, 'index'])->name('system-health');

    // ── CSV Exports ────────────────────────────────────────────────────────
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('/users',      [AdminExportController::class, 'users'])->name('users');
        Route::get('/deposits',   [AdminExportController::class, 'deposits'])->name('deposits');
        Route::get('/withdrawals',[AdminExportController::class, 'withdrawals'])->name('withdrawals');
        Route::get('/trades',     [AdminExportController::class, 'trades'])->name('trades');
        Route::get('/bots',       [AdminExportController::class, 'bots'])->name('bots');
        Route::get('/transactions',[AdminExportController::class, 'transactions'])->name('transactions');
        Route::get('/audit-logs', [AdminExportController::class, 'auditLogs'])->name('audit-logs');
    });

    // ── Legacy stubs (kept for backward compat) ────────────────────────────
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
});

// ─── Webhooks (no CSRF, no auth) ─────────────────────────────────────────────
Route::post('/webhooks/mpesa', [PalPlussWebhookController::class, 'handle'])->name('webhooks.mpesa');
Route::post('/webhooks/b2c',   [PalPlussWebhookController::class, 'handleB2c'])->name('webhooks.b2c');

require __DIR__.'/auth.php';
