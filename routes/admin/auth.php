<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
|
| Mounted by routes/admin.php under the `admin` URI prefix + `admin.` name
| prefix. Resulting URIs: /admin/auth/login, /admin/auth/logout.
|
| Password reset routes (forgot-password, reset-password) are added by
| PR-C, alongside the broker switch in ForgotPasswordController and
| ResetPasswordController.
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.store');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
