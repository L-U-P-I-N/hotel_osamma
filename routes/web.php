<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\UserController;

Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

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
    });
    Route::post('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
        ->name('rooms.updateStatus')
        ->middleware('permission:rooms.manage|rooms.maintenance');

    // Check-in
    Route::middleware('permission:checkin.create')->group(function () {
        Route::get('/checkin', [CheckInController::class, 'create'])->name('checkin.create');
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');
        Route::get('/checkin/{reservation}/success', [CheckInController::class, 'success'])->name('checkin.success');
    });
    Route::get('/checkin/{reservation}/export-gov', [CheckInController::class, 'exportGovernment'])
        ->name('checkin.exportGov')
        ->middleware('permission:government.export');

    // Blacklist check (AJAX)
    Route::get('/guests/blacklist-check', [CheckInController::class, 'blacklistCheck'])
        ->name('guests.blacklistCheck')
        ->middleware('permission:checkin.create|guests.view');

    // Reservations
    Route::middleware('permission:checkin.view')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
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
    Route::middleware('permission:settlement.view')->group(function () {
        Route::get('/settlement', [SettlementController::class, 'index'])->name('settlement.index');
    });
    Route::middleware('permission:settlement.manage')->group(function () {
        Route::post('/settlement/withdrawal', [SettlementController::class, 'addWithdrawal'])->name('settlement.withdrawal');
        Route::post('/settlement/signatures', [SettlementController::class, 'saveSignatures'])->name('settlement.signatures');
    });
    Route::post('/settlement/lock', [SettlementController::class, 'lock'])
        ->name('settlement.lock')
        ->middleware('permission:settlement.lock');

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
        Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/reports/staff', [ReportController::class, 'staffPerformance'])->name('reports.staff');
    });

    // Blacklist
    Route::middleware('permission:blacklist.manage')->group(function () {
        Route::get('/blacklist', [BlacklistController::class, 'index'])->name('blacklist.index');
        Route::post('/blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
        Route::delete('/blacklist/{guest}', [BlacklistController::class, 'remove'])->name('blacklist.remove');
    });

    // Users
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    });
    Route::get('/audit-log', [UserController::class, 'auditLog'])
        ->name('audit.log')
        ->middleware('permission:audit_log.view');
});
