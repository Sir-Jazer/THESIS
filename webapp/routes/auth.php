<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::get('register/regular', [RegisteredUserController::class, 'createRegular'])
        ->name('register.regular');
    Route::post('register/regular', [RegisteredUserController::class, 'storeRegular'])
        ->name('register.regular.store');

    Route::get('register/irregular', [RegisteredUserController::class, 'createIrregularStepOne'])
        ->name('register.irregular');
    Route::post('register/irregular', [RegisteredUserController::class, 'storeIrregularStepOne'])
        ->name('register.irregular.step1.store');

    Route::get('register/irregular/step2', [RegisteredUserController::class, 'createIrregularStepTwo'])
        ->name('register.irregular.step2');
    Route::post('register/irregular/step2', [RegisteredUserController::class, 'storeIrregularStepTwo'])
        ->name('register.irregular.step2.store');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
