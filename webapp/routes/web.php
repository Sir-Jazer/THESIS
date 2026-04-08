<?php

use App\Http\Controllers\AcademicHead\DashboardController as AcademicHeadDashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Proctor\DashboardController as ProctorDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    return redirect(match ($user->role) {
        'student' => route('student.dashboard'),
        'proctor' => route('proctor.dashboard'),
        'cashier' => route('cashier.dashboard'),
        'academic_head' => route('academic-head.dashboard'),
        'admin' => route('admin.dashboard'),
        default => '/',
    });
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard', StudentDashboardController::class)->name('student.dashboard');
    });

    Route::middleware('role:proctor')->group(function () {
        Route::get('/proctor/dashboard', ProctorDashboardController::class)->name('proctor.dashboard');
    });

    Route::middleware('role:cashier')->group(function () {
        Route::get('/cashier/dashboard', CashierDashboardController::class)->name('cashier.dashboard');
    });

    Route::middleware('role:academic_head')->group(function () {
        Route::get('/academic-head/dashboard', AcademicHeadDashboardController::class)->name('academic-head.dashboard');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', AdminDashboardController::class)->name('admin.dashboard');

        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/proctor/create', [AdminUserController::class, 'createProctor'])->name('admin.users.proctor.create');
        Route::post('/admin/users/proctor', [AdminUserController::class, 'storeProctor'])->name('admin.users.proctor.store');
        Route::get('/admin/users/cashier/create', [AdminUserController::class, 'createCashier'])->name('admin.users.cashier.create');
        Route::post('/admin/users/cashier', [AdminUserController::class, 'storeCashier'])->name('admin.users.cashier.store');
        Route::get('/admin/users/academic-head/create', [AdminUserController::class, 'createAcademicHead'])->name('admin.users.academic-head.create');
        Route::post('/admin/users/academic-head', [AdminUserController::class, 'storeAcademicHead'])->name('admin.users.academic-head.store');
        Route::patch('/admin/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('admin.users.status.update');
        Route::post('/admin/users/{user}/send-reset', [AdminUserController::class, 'sendReset'])->name('admin.users.send-reset');

        Route::resource('admin/rooms', AdminRoomController::class)->except(['show'])->names('admin.rooms');
        Route::resource('admin/programs', AdminProgramController::class)->except(['show'])->names('admin.programs');
        Route::resource('admin/programs.sections', AdminSectionController::class)->except(['show'])->names('admin.programs.sections');
        Route::resource('admin/subjects', AdminSubjectController::class)->except(['show'])->names('admin.subjects');

        Route::get('/admin/settings', [AdminSettingController::class, 'edit'])->name('admin.settings.edit');
        Route::put('/admin/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');
    });
});

require __DIR__.'/auth.php';
