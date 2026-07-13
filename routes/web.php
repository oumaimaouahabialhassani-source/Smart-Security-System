<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AccessPermissionController;
use App\Http\Controllers\AiSecurityBotController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLogin'])->name('login');

    Route::get('/login', [LoginController::class, 'showLogin']);

    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    // Public self-registration is intentionally disabled: accounts are
    // created by administrators from the Users module (invitation email).

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [\App\Http\Controllers\NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');

    // Route::view (not a closure) so `php artisan route:cache` works.
    Route::view('/help', 'help.index')->name('help.index');

    Route::patch('/users/{user}/role', [UserController::class, 'role'])->name('users.role');
    Route::resource('users', UserController::class)->middleware('module:users');

    Route::get('/cameras/live', [CameraController::class, 'live'])->name('cameras.live');
    Route::get('/cameras/live/feed', [CameraController::class, 'liveFeed'])->name('cameras.live-feed');
    Route::resource('cameras', CameraController::class);

    Route::resource('devices', DeviceController::class)->middleware('module:devices');

    Route::middleware('module:visitors')->group(function () {
        Route::get('/visitors/export', [VisitorController::class, 'export'])->name('visitors.export');
        Route::post('/visitors/{visitor}/check-in', [VisitorController::class, 'checkIn'])->name('visitors.check-in');
        Route::post('/visitors/{visitor}/check-out', [VisitorController::class, 'checkOut'])->name('visitors.check-out');
        Route::get('/visitors/{visitor}/badge', [VisitorController::class, 'badge'])->name('visitors.badge');
        Route::get('/visitors/{visitor}/pass', [VisitorController::class, 'pass'])->name('visitors.pass');
        Route::resource('visitors', VisitorController::class);
    });

    Route::middleware('module:biometrics')->group(function () {
    Route::get('/biometrics/logs', [BiometricController::class, 'logs'])->name('biometrics.logs');
    Route::get('/biometrics/logs/export', [BiometricController::class, 'exportLogs'])->name('biometrics.logs.export');
    Route::get('/biometrics/export', [BiometricController::class, 'exportProfiles'])->name('biometrics.export');
    Route::post('/biometrics/{biometric}/enroll-face', [BiometricController::class, 'enrollFace'])->name('biometrics.enroll-face');
    Route::post('/biometrics/{biometric}/enroll-fingerprint', [BiometricController::class, 'enrollFingerprint'])->name('biometrics.enroll-fingerprint');
    Route::post('/biometrics/{biometric}/enroll-iris', [BiometricController::class, 'enrollIris'])->name('biometrics.enroll-iris');
    Route::post('/biometrics/{biometric}/verify', [BiometricController::class, 'verify'])->name('biometrics.verify');
    Route::post('/biometrics/devices/{device}/restart', [BiometricController::class, 'restartDevice'])->name('biometrics.device-restart');
    Route::post('/biometrics/devices/{device}/sync', [BiometricController::class, 'syncDevice'])->name('biometrics.device-sync');
    Route::resource('biometrics', BiometricController::class)->parameters(['biometrics' => 'biometric']);
    });

    Route::middleware('module:access')->group(function () {
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::get('/access/logs', [AccessController::class, 'logs'])->name('access.logs');
    Route::get('/access/logs/export', [AccessController::class, 'exportLogs'])->name('access.logs.export');
    Route::get('/access/feed', [AccessController::class, 'feed'])->name('access.feed');
    Route::get('/access/permissions/export', [AccessController::class, 'exportPermissions'])->name('access.permissions.export');
    Route::post('/access/doors/lock-all', [AccessController::class, 'lockAll'])->name('access.lock-all');
    Route::post('/access/doors/{door}/lock', [AccessController::class, 'lockDoor'])->name('access.door-lock');
    Route::post('/access/doors/{door}/unlock', [AccessController::class, 'unlockDoor'])->name('access.door-unlock');
    Route::get('/access/permissions/create', [AccessPermissionController::class, 'create'])->name('access.permissions.create');
    Route::post('/access/permissions', [AccessPermissionController::class, 'store'])->name('access.permissions.store');
    Route::get('/access/permissions/{permission}/edit', [AccessPermissionController::class, 'edit'])->name('access.permissions.edit');
    Route::put('/access/permissions/{permission}', [AccessPermissionController::class, 'update'])->name('access.permissions.update');
    Route::delete('/access/permissions/{permission}', [AccessPermissionController::class, 'destroy'])->name('access.permissions.destroy');
    Route::post('/access/temporary', [AccessPermissionController::class, 'storeTemporary'])->name('access.temporary');
    });

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/feed', [AlertController::class, 'feed'])->name('alerts.feed');
    Route::get('/alerts/export', [AlertController::class, 'export'])->name('alerts.export');
    Route::post('/alerts/acknowledge-all', [AlertController::class, 'acknowledgeAll'])->name('alerts.acknowledge-all');
    Route::post('/alerts/preferences', [AlertController::class, 'savePreferences'])->name('alerts.preferences');
    Route::patch('/alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');

    // AI Security Bot — administrators and security officers only.
    Route::middleware('ai.bot')->prefix('ai-bot')->name('ai.')->group(function () {
        Route::get('/', [AiSecurityBotController::class, 'dashboard'])->name('dashboard');
        Route::get('/feed', [AiSecurityBotController::class, 'feed'])->name('feed');
        Route::post('/scan', [AiSecurityBotController::class, 'scan'])->name('scan');
        Route::get('/analytics', [\App\Http\Controllers\AiAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/alerts', [AiSecurityBotController::class, 'alerts'])->name('alerts');
        Route::patch('/alerts/{aiAlert}', [AiSecurityBotController::class, 'update'])->name('alerts.update');
        Route::post('/alerts/{aiAlert}/resolve', [AiSecurityBotController::class, 'resolve'])->name('alerts.resolve');
        Route::delete('/alerts/{aiAlert}', [AiSecurityBotController::class, 'destroy'])->name('alerts.destroy');
        Route::get('/history', [AiSecurityBotController::class, 'history'])->name('history');
        Route::get('/history/export', [AiSecurityBotController::class, 'export'])->name('export');
        Route::get('/report', [AiSecurityBotController::class, 'report'])->name('report');
        Route::get('/chat', [AiSecurityBotController::class, 'chat'])->name('chat');
        Route::post('/chat', [AiSecurityBotController::class, 'chatMessage'])->name('chat.message');
    });

    Route::middleware('module:settings')->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/{group}', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/settings/backups', [SettingController::class, 'backupCreate'])->name('settings.backup-create');
    Route::get('/settings/backups/{file}/download', [SettingController::class, 'backupDownload'])->name('settings.backup-download');
    Route::post('/settings/backups/{file}/restore', [SettingController::class, 'backupRestore'])->name('settings.backup-restore');
    Route::delete('/settings/backups/{file}', [SettingController::class, 'backupDelete'])->name('settings.backup-delete');
    });

    Route::get('/audit', [\App\Http\Controllers\AuditLogController::class, 'index'])->middleware('module:audit')->name('audit.index');
    Route::get('/audit/export', [\App\Http\Controllers\AuditLogController::class, 'export'])->middleware('module:audit')->name('audit.export');

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->middleware('module:reports')->name('reports.index');
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'export'])->middleware('module:reports')->name('reports.export');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
