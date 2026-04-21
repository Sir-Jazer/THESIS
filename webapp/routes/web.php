<?php

use App\Http\Controllers\AcademicHead\DashboardController as AcademicHeadDashboardController;
use App\Http\Controllers\AcademicHead\GeneralExamMatrixController as AcademicHeadGeneralExamMatrixController;
use App\Http\Controllers\AcademicHead\ReportController as AcademicHeadReportController;
use App\Http\Controllers\AcademicHead\ScheduleController as AcademicHeadScheduleController;
use App\Http\Controllers\AcademicHead\SubjectExamReferenceController as AcademicHeadSubjectExamReferenceController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboardController;
use App\Http\Controllers\Cashier\StudentPaymentController as CashierStudentPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Proctor\AdviseeController as ProctorAdviseeController;
use App\Http\Controllers\Proctor\DashboardController as ProctorDashboardController;
use App\Http\Controllers\Proctor\PendingRegistrationController as ProctorPendingRegistrationController;
use App\Http\Controllers\Proctor\ScheduleController as ProctorScheduleController;
use App\Http\Controllers\Proctor\ScannerController as ProctorScannerController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PermitController as StudentPermitController;
use App\Http\Controllers\Student\SubjectController as StudentSubjectController;
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

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::get('/subjects', [StudentSubjectController::class, 'index'])->name('subjects.index');
        Route::get('/permit', [StudentPermitController::class, 'show'])->name('permit.show');
    });

    Route::middleware('role:proctor')->prefix('proctor')->name('proctor.')->group(function () {
        Route::get('/dashboard', ProctorDashboardController::class)->name('dashboard');
        Route::get('/scanner', [ProctorScannerController::class, 'show'])->name('scanner.show');
        Route::post('/scanner/scan', [ProctorScannerController::class, 'scan'])->name('scanner.scan');
        Route::get('/schedules', [ProctorScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/slots/{slot}/attendance', [ProctorScheduleController::class, 'attendance'])->name('schedules.attendance');
        Route::get('/advisees', [ProctorAdviseeController::class, 'index'])->name('advisees.index');
        Route::get('/pending-registrations', [ProctorPendingRegistrationController::class, 'index'])->name('pending-registrations.index');
        Route::post('/pending-registrations/{user}/approve', [ProctorPendingRegistrationController::class, 'approve'])->name('pending-registrations.approve');
        Route::post('/pending-registrations/{user}/reject', [ProctorPendingRegistrationController::class, 'reject'])->name('pending-registrations.reject');
    });

    Route::middleware('role:cashier')->prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/dashboard', CashierDashboardController::class)->name('dashboard');
        Route::get('/student-payments', [CashierStudentPaymentController::class, 'index'])->name('student-payments.index');
        Route::post('/student-payments/{studentProfile}/generate', [CashierStudentPaymentController::class, 'generate'])->name('student-payments.generate');
        Route::patch('/student-payments/{studentProfile}/revoke', [CashierStudentPaymentController::class, 'revoke'])->name('student-payments.revoke');
    });

    Route::middleware('role:academic_head')->group(function () {
        Route::get('/academic-head/dashboard', AcademicHeadDashboardController::class)->name('academic-head.dashboard');
        Route::get('/academic-head/schedules', [AcademicHeadScheduleController::class, 'index'])->name('academic-head.schedules.index');
        Route::post('/academic-head/schedules/load', [AcademicHeadScheduleController::class, 'load'])->name('academic-head.schedules.load');
        Route::post('/academic-head/schedules/{schedule}/fetch-matrix', [AcademicHeadScheduleController::class, 'fetchMatrix'])->name('academic-head.schedules.fetch-matrix');
        Route::post('/academic-head/schedules/generate', [AcademicHeadScheduleController::class, 'generate'])->name('academic-head.schedules.generate');
        Route::get('/academic-head/schedules/{schedule}/edit', [AcademicHeadScheduleController::class, 'edit'])->name('academic-head.schedules.edit');
        Route::patch('/academic-head/schedules/slots/{slot}', [AcademicHeadScheduleController::class, 'updateSlot'])->name('academic-head.schedules.slots.update');
        Route::post('/academic-head/schedules/{schedule}/save-draft', [AcademicHeadScheduleController::class, 'saveDraft'])->name('academic-head.schedules.save-draft');
        Route::post('/academic-head/schedules/{schedule}/upload', [AcademicHeadScheduleController::class, 'upload'])->name('academic-head.schedules.upload');
        Route::post('/academic-head/schedules/{schedule}/reset', [AcademicHeadScheduleController::class, 'reset'])->name('academic-head.schedules.reset');
        Route::delete('/academic-head/schedules/{schedule}', [AcademicHeadScheduleController::class, 'destroy'])->name('academic-head.schedules.destroy');

        Route::get('/academic-head/general-exam-matrix', [AcademicHeadGeneralExamMatrixController::class, 'index'])->name('academic-head.general-exam-matrix.index');
        Route::get('/academic-head/general-exam-matrix/create', [AcademicHeadGeneralExamMatrixController::class, 'create'])->name('academic-head.general-exam-matrix.create');
        Route::post('/academic-head/general-exam-matrix', [AcademicHeadGeneralExamMatrixController::class, 'store'])->name('academic-head.general-exam-matrix.store');
        Route::post('/academic-head/general-exam-matrix/{matrix}/upload', [AcademicHeadGeneralExamMatrixController::class, 'upload'])->name('academic-head.general-exam-matrix.upload');
        Route::get('/academic-head/general-exam-matrix/{matrix}/edit', [AcademicHeadGeneralExamMatrixController::class, 'edit'])->name('academic-head.general-exam-matrix.edit');
        Route::put('/academic-head/general-exam-matrix/{matrix}', [AcademicHeadGeneralExamMatrixController::class, 'update'])->name('academic-head.general-exam-matrix.update');
        Route::delete('/academic-head/general-exam-matrix/{matrix}', [AcademicHeadGeneralExamMatrixController::class, 'destroy'])->name('academic-head.general-exam-matrix.destroy');
        Route::get('/academic-head/subject-exam-references', [AcademicHeadSubjectExamReferenceController::class, 'index'])->name('academic-head.subject-exam-references.index');
        Route::put('/academic-head/subject-exam-references', [AcademicHeadSubjectExamReferenceController::class, 'update'])->name('academic-head.subject-exam-references.update');
        Route::get('/academic-head/reports', AcademicHeadReportController::class)->name('academic-head.reports');
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
        Route::patch('/admin/users/{user}/advisory-section', [AdminUserController::class, 'updateAdvisorySection'])->name('admin.users.advisory-section.update');
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
