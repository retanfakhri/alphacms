<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\TwoFactorChallengeController;

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
|
| Mounted by routes/admin.php under the `admin` URI prefix + `admin.` name
| prefix. Resulting URIs live under /admin/auth/*.
|
| Password reset is wired through the dedicated `admins` password broker
| (see config/auth.php) which is backed by AdminEligibleUserProvider —
| requests for users without `access_admin_panel` produce the same
| response as requests for admin-eligible users, preventing enumeration.
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.store');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    // Admin-owned two-factor challenge — self-contained under /admin/auth/.
    // Resulting names: admin.auth.two-factor.login + admin.auth.two-factor.store.
    Route::get('two-factor-challenge',  [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.store');
});
