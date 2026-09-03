<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Students
    Route::get('/students/template/download', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::get('/students/upload', [StudentController::class, 'showUploadForm'])->name('students.upload.form');
    Route::post('/students/upload', [StudentController::class, 'upload'])->name('students.upload');
    Route::resource('students', StudentController::class);

    // Marks
    Route::get('/marks/upload', [MarkController::class, 'showUploadForm'])->name('marks.upload.form');
    Route::post('/marks/upload', [MarkController::class, 'upload'])->name('marks.upload');
    Route::resource('marks', MarkController::class);

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // Payments
    Route::resource('payments', PaymentController::class);

    // Reports
    Route::get('/reports/student/{id}', [ReportController::class, 'showStudentReport'])->name('reports.student');
    Route::get('/reports/leaders', [ReportController::class, 'showLeadersReport'])->name('reports.leaders');

    // Admin Control Panel
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
        Route::get('/users/create', [AdminController::class, 'userCreate'])->name('users.create');
        Route::post('/users', [AdminController::class, 'userStore'])->name('users.store');
        Route::get('/users/{id}/edit', [AdminController::class, 'userEdit'])->name('users.edit');
        Route::put('/users/{id}', [AdminController::class, 'userUpdate'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'userDestroy'])->name('users.destroy');

        Route::get('/schools', [AdminController::class, 'schoolsIndex'])->name('schools.index');
        Route::post('/schools', [AdminController::class, 'schoolStore'])->name('schools.store');

        Route::get('/subjects', [AdminController::class, 'subjectsIndex'])->name('subjects.index');
        Route::post('/subjects', [AdminController::class, 'subjectStore'])->name('subjects.store');

        Route::get('/timetable', [AdminController::class, 'timetableIndex'])->name('timetable.index');
        Route::post('/timetable', [AdminController::class, 'timetableStore'])->name('timetable.store');
    });
});
