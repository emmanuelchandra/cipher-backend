<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\FaceController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\ShiftController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Departments (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::put('/departments/{id}', [DepartmentController::class, 'update']);
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
    });

    // Shifts (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index']);
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{id}', [ShiftController::class, 'update']);
        Route::delete('/shifts/{id}', [ShiftController::class, 'destroy']);
    });

    // Employees
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees/assign-shift', [EmployeeController::class, 'assignShift']);
    Route::post('/employees/upload-signature', [EmployeeController::class, 'uploadSignature']);

    // Face Recognition
    Route::post('/face/register', [FaceController::class, 'register']);
    Route::post('/face/check-in', [FaceController::class, 'checkIn']);
    Route::post('/face/check-out', [FaceController::class, 'checkOut']);
    Route::get('/face/descriptor/{userId}', [FaceController::class, 'descriptor']);

    // Attendance
    Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard']);
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/history/{userId}', [AttendanceController::class, 'history']);
    Route::get('/attendance/late-today', [AttendanceController::class, 'lateToday']);
    Route::get('/attendance/anomalies', [AttendanceController::class, 'anomalies']);

    // Reports (HR and Admin)
    Route::middleware('role:admin,hr')->group(function () {
        Route::get('/reports/pending', [ReportController::class, 'pending']);
        Route::post('/reports/generate/{employeeId}/{month}/{year}', [ReportController::class, 'generate']);
        Route::post('/reports/{id}/approve', [ReportController::class, 'approve']);
        Route::get('/reports/download/{id}', [ReportController::class, 'download']);
    });

    // Company Settings (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings/upload-stamp', [SettingsController::class, 'uploadStamp']);
        Route::put('/settings', [SettingsController::class, 'update']);
    });
});
