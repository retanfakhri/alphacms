<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('admin/dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'logoutSession'])->name('profile.sessions.destroy');
    Route::delete('/profile/sessions', [ProfileController::class, 'logoutOtherSessions'])->name('profile.sessions.purge');

    // User Management
    Route::get('users/trash', [UserController::class, 'trash'])->name('users.trash');
    Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force', [UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('users', UserController::class);

    // Roles and Permissions
    Route::resource('roles', RoleController::class);
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('permissions/groups', [PermissionController::class, 'groups'])->name('permissions.groups');
    Route::post('permissions/groups', [PermissionController::class, 'storeGroup'])->name('permissions.groups.store');

    // Settings
    Route::get('settings/general', [\App\Http\Controllers\Admin\SettingController::class, 'general'])->name('settings.general');
    Route::post('settings/general', [\App\Http\Controllers\Admin\SettingController::class, 'updateGeneral'])->name('settings.general.update');
    Route::post('settings/smtp', [\App\Http\Controllers\Admin\SettingController::class, 'updateSmtp'])->name('settings.smtp.update');
    Route::post('settings/social', [\App\Http\Controllers\Admin\SettingController::class, 'updateSocial'])->name('settings.social.update');
    Route::post('settings/tracking', [\App\Http\Controllers\Admin\SettingController::class, 'updateTracking'])->name('settings.tracking.update');
    Route::post('settings/media', [\App\Http\Controllers\Admin\SettingController::class, 'uploadMedia'])->name('settings.media.upload');
    Route::delete('settings/media', [\App\Http\Controllers\Admin\SettingController::class, 'clearMedia'])->name('settings.media.clear');

    Route::get('settings/third-party', [\App\Http\Controllers\Admin\SettingController::class, 'thirdParty'])->name('settings.third-party');
    Route::post('settings/third-party', [\App\Http\Controllers\Admin\SettingController::class, 'updateThirdParty'])->name('settings.third-party.update');

    Route::get('settings/cdn', [\App\Http\Controllers\Admin\SettingController::class, 'cdn'])->name('settings.cdn');
    Route::post('settings/cdn', [\App\Http\Controllers\Admin\SettingController::class, 'updateCdn'])->name('settings.cdn.update');
});
