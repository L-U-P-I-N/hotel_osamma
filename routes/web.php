<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\FloorController;

Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Password Reset via Backup Code
Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'resetWithBackupCode'])->name('password.reset-backup');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    // Rooms
    Route::middleware('permission:rooms.view')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::get('/rooms/available', [RoomController::class, 'available'])->name('rooms.available');
    });
    Route::middleware('permission:rooms.manage')->group(function () {
        Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::get('/floors', [FloorController::class, 'index'])->name('floors.index');
        Route::post('/floors', [FloorController::class, 'store'])->name('floors.store');
        Route::put('/floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
        Route::delete('/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');
    });
    Route::get('/floors/{floor}/room-numbers', [FloorController::class, 'availableRoomNumbers'])->name('floors.roomNumbers')->middleware('auth');
    Route::post('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
        ->name('rooms.updateStatus')
        ->middleware('permission:rooms.manage|rooms.maintenance');
    Route::get('/floors/{floor}/rooms', [FloorController::class, 'availableRoomNumbers'])->name('floors.roomNumbers')->middleware('auth');

    // Check-in
    Route::middleware('permission:checkin.create')->group(function () {
        Route::get('/checkin', [CheckInController::class, 'create'])->name('checkin.create');
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');
        Route::get('/checkin/{reservation}/success', [CheckInController::class, 'success'])->name('checkin.success');
    });
    Route::get('/checkin/{reservation}/export-gov', [CheckInController::class, 'exportGovernment'])
        ->name('checkin.exportGov')
        ->middleware('permission:government.export');

    // Guest name autocomplete (AJAX)
    Route::get('/guests/search', [CheckInController::class, 'guestSearch'])
        ->name('guests.search')
        ->middleware('permission:checkin.create|guests.view');

    // Guest lookup for auto-fill (AJAX)
    Route::get('/guests/lookup', [CheckInController::class, 'guestLookup'])
        ->name('guests.lookup')
        ->middleware('permission:checkin.create|guests.view');

    // Reservations
    Route::middleware('permission:checkin.view')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::patch('/reservations/{reservation}/arrive', [ReservationController::class, 'arrive'])->name('reservations.arrive');
        Route::post('/reservations/{reservation}/renew', [ReservationController::class, 'renew'])->name('reservations.renew');
        Route::post('/reservations/{reservation}/transfer-room', [ReservationController::class, 'transferRoom'])->name('reservations.transferRoom');
    });

    // Check-out
    Route::middleware('permission:checkout.process')->group(function () {
        Route::get('/checkout/{reservation}', [CheckOutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout/{reservation}', [CheckOutController::class, 'process'])->name('checkout.process');
    });

    // Payments
    Route::post('/payments', [PaymentController::class, 'store'])
        ->name('payments.store')
        ->middleware('permission:payments.create');
    Route::get('/payments/{file}/receipt', [PaymentController::class, 'viewReceipt'])
        ->name('payments.receipt')
        ->middleware('permission:payments.bank_receipt')
        ->where('file', '.*');

    // Cash Settlement
    Route::middleware('permission:shifts.view')->group(function () {
        Route::get('/settlement', [SettlementController::class, 'index'])->name('settlement.index');
    });
    Route::middleware('permission:shifts.view')->group(function () {
        Route::post('/settlement/withdrawal', [SettlementController::class, 'addWithdrawal'])->name('settlement.withdrawal');
        Route::post('/settlement/signatures', [SettlementController::class, 'saveSignatures'])->name('settlement.signatures');
        Route::post('/settlement/lock', [SettlementController::class, 'lock'])->name('settlement.lock');
    });

    // Shifts
    Route::middleware('permission:shifts.view')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts/close', [ShiftController::class, 'close'])->name('shifts.close');
        Route::get('/shifts/{shift}/pdf', [ShiftController::class, 'exportPdf'])->name('shifts.pdf');
    });
    Route::post('/shifts/withdrawal', [ShiftController::class, 'addWithdrawal'])->name('shifts.withdrawal')->middleware('permission:withdrawal.create');

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', fn() => redirect()->route('reports.occupancy'))->name('reports.index');
        Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
        Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/reports/staff', [ReportController::class, 'staffPerformance'])->name('reports.staff');
        Route::get('/reports/daily', [DailyReportController::class, 'index'])->name('reports.daily');
        Route::get('/reports/daily/pdf', [DailyReportController::class, 'exportPdf'])->name('reports.daily.pdf');
        Route::get('/reports/daily/excel', [DailyReportController::class, 'exportExcel'])->name('reports.daily.excel');
    });

    // Users
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
        Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
        Route::post('/users/{user}/permissions', [UserController::class, 'togglePermission'])->name('users.togglePermission');
        Route::post('/users/{user}/regenerate-backup-code', [UserController::class, 'regenerateBackupCode'])->name('users.regenerateBackupCode');
    });
    Route::get('/audit-log', [UserController::class, 'auditLog'])
        ->name('audit.log')
        ->middleware('permission:audit_log.view');
});
