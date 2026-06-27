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
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\SeasonalPriceController;
use App\Http\Controllers\GuestController;

Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/login/force', [AuthController::class, 'forceLogin'])->name('login.force');
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
    Route::middleware('permission:rooms.create')->group(function () {
        Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/floors', [FloorController::class, 'index'])->name('floors.index');
        Route::post('/floors', [FloorController::class, 'store'])->name('floors.store');
    });
    Route::middleware('permission:rooms.edit')->group(function () {
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::put('/floors/{floor}', [FloorController::class, 'update'])->name('floors.update');
    });
    Route::middleware('permission:rooms.delete')->group(function () {
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::delete('/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');
    });
    Route::get('/floors/{floor}/room-numbers', [FloorController::class, 'availableRoomNumbers'])->name('floors.roomNumbers')->middleware(['auth', 'permission:rooms.view']);
    Route::post('/rooms/bulk-price', [RoomController::class, 'bulkUpdatePrice'])
        ->name('rooms.bulkPrice')
        ->middleware('permission:rooms.edit');
    Route::post('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
        ->name('rooms.updateStatus')
        ->middleware('permission:rooms.edit|rooms.maintenance');

    // Check-in
    Route::middleware('permission:checkin.create')->group(function () {
        Route::get('/checkin', [CheckInController::class, 'create'])->name('checkin.create');
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');
        Route::get('/checkin/{reservation}/success', [CheckInController::class, 'success'])->name('checkin.success');
    });
    Route::get('/checkin/{reservation}/export-gov', [CheckInController::class, 'exportGovernment'])
        ->name('checkin.exportGov')
        ->middleware('permission:government.export');

    // Private ID images & documents — visible to anyone who can view reservations
    Route::get('/guests/{guest}/id-image', [CheckInController::class, 'serveGuestIdImage'])
        ->name('guests.idImage')
        ->middleware('permission:checkin.view');
    Route::get('/companions/{companion}/id-image', [CheckInController::class, 'serveCompanionIdImage'])
        ->name('companions.idImage')
        ->middleware('permission:checkin.view');
    Route::get('/companions/{companion}/marriage-doc', [CheckInController::class, 'serveMarriageDoc'])
        ->name('companions.marriageDoc')
        ->middleware('permission:checkin.view');

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
        Route::get('/reservations', fn() => redirect()->route('reservations.expiring'))->name('reservations.index');
        Route::get('/reservations-expiring', [ReservationController::class, 'expiring'])->name('reservations.expiring');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::patch('/reservations/{reservation}/checkin', [ReservationController::class, 'checkin'])->name('reservations.checkin')->middleware('permission:checkin.create');
        Route::post('/reservations/{reservation}/renew', [ReservationController::class, 'renew'])->name('reservations.renew');
        Route::post('/reservations/{reservation}/transfer-room', [ReservationController::class, 'transferRoom'])->name('reservations.transferRoom');
    });

    // Check-out
    Route::middleware('permission:checkout.process')->group(function () {
        Route::get('/checkout/{reservation}', [CheckOutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout/{reservation}', [CheckOutController::class, 'process'])->name('checkout.process');
        Route::get('/checkout/{reservation}/done', [CheckOutController::class, 'done'])->name('checkout.done');
    });

    // Payments
    Route::post('/payments', [PaymentController::class, 'store'])
        ->name('payments.store')
        ->middleware('permission:payments.create');
    Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])
        ->name('payments.slip')
        ->middleware('permission:payments.create');
    Route::get('/payments/{file}/receipt', [PaymentController::class, 'viewReceipt'])
        ->name('payments.receipt')
        ->middleware('permission:payments.bank_receipt')
        ->where('file', '.*');

    // Cash Settlement (redirected to shifts)
    Route::get('/settlement', fn() => redirect()->route('shifts.index'))->name('settlement.index');
    Route::post('/settlement/lock', fn() => redirect()->route('shifts.index'))->name('settlement.lock');
    Route::post('/settlement/withdrawal', fn() => redirect()->route('shifts.index'))->name('settlement.withdrawal');
    Route::post('/settlement/signatures', fn() => redirect()->route('shifts.index'))->name('settlement.signatures');

    // Shifts
    Route::middleware('permission:shifts.view')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts/close', [ShiftController::class, 'close'])->name('shifts.close');
        Route::get('/shifts/{shift}/pdf', [ShiftController::class, 'exportPdf'])->name('shifts.pdf');
        Route::get('/shifts/{shift}/handover', [ShiftController::class, 'handover'])->name('shifts.handover');
    });
    Route::post('/shifts/withdrawal', [ShiftController::class, 'addWithdrawal'])->name('shifts.withdrawal')->middleware('permission:withdrawal.create');
    Route::patch('/shifts/withdrawals/{withdrawal}', [ShiftController::class, 'updateWithdrawal'])->name('shifts.withdrawal.update')->middleware('permission:withdrawal.edit');
    Route::delete('/shifts/withdrawals/{withdrawal}', [ShiftController::class, 'destroyWithdrawal'])->name('shifts.withdrawal.destroy')->middleware('permission:withdrawal.delete');
    Route::post('/shifts/{shift}/reopen', [ShiftController::class, 'reopen'])->name('shifts.reopen')->middleware('permission:shifts.reopen');
    Route::post('/shifts/{shift}/deduct-salary', [ShiftController::class, 'deductSalary'])->name('shifts.deductSalary')->middleware('permission:users.manage');

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', fn() => redirect()->route('reports.dailyHub'))->name('reports.index');
        Route::get('/reports/daily-hub', [ReportController::class, 'dailyHub'])->name('reports.dailyHub');
        Route::get('/reports/occupancy/pdf', [ReportController::class, 'occupancyPdf'])->name('reports.occupancy.pdf');
        Route::get('/reports/occupancy/excel', [ReportController::class, 'occupancyExcel'])->name('reports.occupancy.excel');
        Route::get('/reports/revenue/pdf', [ReportController::class, 'revenuePdf'])->name('reports.revenue.pdf');
        Route::get('/reports/revenue/excel', [ReportController::class, 'revenueExcel'])->name('reports.revenue.excel');
        Route::get('/reports/staff/pdf', [ReportController::class, 'staffPdf'])->name('reports.staff.pdf');
        Route::get('/reports/staff/excel', [ReportController::class, 'staffExcel'])->name('reports.staff.excel');
        Route::get('/reports/daily/pdf', [DailyReportController::class, 'exportPdf'])->name('reports.daily.pdf');
        Route::get('/reports/daily/excel', [DailyReportController::class, 'exportExcel'])->name('reports.daily.excel');
        Route::get('/reports/reservations/pdf', [ReportController::class, 'reservationsPdf'])->name('reports.reservations.pdf');
        Route::get('/reports/reservations/excel', [ReportController::class, 'reservationsExcel'])->name('reports.reservations.excel');
        Route::get('/reports/rooms/pdf', [ReportController::class, 'roomsPdf'])->name('reports.rooms.pdf');
        Route::get('/reports/rooms/excel', [ReportController::class, 'roomsExcel'])->name('reports.rooms.excel');
        Route::get('/reports/guests/pdf', [ReportController::class, 'guestsPdf'])->name('reports.guests.pdf');
        Route::get('/reports/guests/excel', [ReportController::class, 'guestsExcel'])->name('reports.guests.excel');
        Route::get('/reports/debts', [ReportController::class, 'debts'])->name('reports.debts');
        Route::get('/reports/debts/pdf', [ReportController::class, 'debtsPdf'])->name('reports.debts.pdf');
        Route::get('/reports/debts/excel', [ReportController::class, 'debtsExcel'])->name('reports.debts.excel');
        Route::get('/reports/partial-payments', [ReportController::class, 'partialPayments'])->name('reports.partialPayments');
        Route::get('/reports/salaries/pdf', [ReportController::class, 'salariesPdf'])->name('reports.salaries.pdf');
        Route::get('/reports/salaries/excel', [ReportController::class, 'salariesExcel'])->name('reports.salaries.excel');
        Route::get('/reports/shifts/pdf', [ReportController::class, 'shiftsPdf'])->name('reports.shifts.pdf');
        Route::get('/reports/shifts/excel', [ReportController::class, 'shiftsExcel'])->name('reports.shifts.excel');
        Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profitLoss');
        Route::get('/reports/shifts-hub', [ReportController::class, 'shiftsHub'])->name('reports.shiftsHub');
        Route::get('/reports/daily-close/pdf', [ReportController::class, 'dailyClosePdf'])->name('reports.dailyClose.pdf');
        Route::get('/reports/finance-hub', [ReportController::class, 'financeHub'])->name('reports.financeHub');
        Route::get('/reports/hr-hub', [ReportController::class, 'hrHub'])->name('reports.hrHub');
        Route::get('/reports/guests-rooms-hub', [ReportController::class, 'guestsRoomsHub'])->name('reports.guestsRoomsHub');
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

    // ===== HR Module =====
    // Employees
    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    });
    Route::middleware('permission:hr.create')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    });
    Route::middleware('permission:hr.edit')->group(function () {
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });
    Route::middleware('permission:hr.delete')->group(function () {
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    // Salaries
    // Guest statement
    Route::middleware('permission:checkin.view')->group(function () {
        Route::get('/guests/{guest}/statement', [GuestController::class, 'statement'])->name('guests.statement');
        Route::get('/guests/{guest}/statement/pdf', [GuestController::class, 'statementPdf'])->name('guests.statement.pdf');
    });

    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/salaries', [SalaryController::class, 'index'])->name('salaries.index');
    });
    Route::middleware('permission:hr.create')->group(function () {
        Route::get('/salaries/create', [SalaryController::class, 'create'])->name('salaries.create');
        Route::post('/salaries', [SalaryController::class, 'store'])->name('salaries.store');
    });
    Route::middleware('permission:hr.edit')->group(function () {
        Route::patch('/salaries/{salary}/mark-paid', [SalaryController::class, 'markPaid'])->name('salaries.markPaid');
        Route::get('/salaries/{salary}/edit', [SalaryController::class, 'edit'])->name('salaries.edit');
        Route::put('/salaries/{salary}', [SalaryController::class, 'update'])->name('salaries.update');
        Route::get('/salaries/{salary}/pdf', [SalaryController::class, 'pdf'])->name('salaries.pdf');
    });
    Route::middleware('permission:hr.delete')->group(function () {
        Route::delete('/salaries/{salary}', [SalaryController::class, 'destroy'])->name('salaries.destroy');
    });

    // Attendance
    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
    });
    Route::middleware('permission:attendance.create')->group(function () {
        Route::post('/attendance/daily', [AttendanceController::class, 'saveDaily'])->name('attendance.saveDaily');
    });

    // Leaves
    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    });
    Route::middleware('permission:hr.create')->group(function () {
        Route::get('/leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    });
    Route::middleware('permission:hr.delete')->group(function () {
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
    });

    // ===== Expense Module =====
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/deferred', [ExpenseController::class, 'deferred'])->name('expenses.deferred');
        Route::get('/expenses/export/excel', [ExpenseController::class, 'exportExcel'])->name('expenses.excel');
        Route::get('/expenses/export/pdf', [ExpenseController::class, 'exportPdf'])->name('expenses.pdf');
    });
    Route::middleware('permission:expenses.edit')->group(function () {
        Route::patch('/expenses/{expense}/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
    });
    Route::middleware('permission:expenses.create')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    });
    Route::middleware('permission:expenses.edit')->group(function () {
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    });
    Route::middleware('permission:expenses.delete')->group(function () {
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    // ===== Refunds Module =====
    Route::middleware('permission:checkin.view')->group(function () {
        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/refunds/create/{reservation}', [RefundController::class, 'create'])->name('refunds.create');
    });
    Route::middleware('permission:payments.create')->group(function () {
        Route::post('/refunds/{reservation}', [RefundController::class, 'store'])->name('refunds.store');
    });

    // ===== Seasonal Pricing =====
    Route::middleware('permission:rooms.edit')->group(function () {
        Route::get('/seasonal-prices', [SeasonalPriceController::class, 'index'])->name('seasonal-prices.index');
        Route::post('/seasonal-prices', [SeasonalPriceController::class, 'store'])->name('seasonal-prices.store');
        Route::get('/seasonal-prices/{seasonalPrice}/edit', [SeasonalPriceController::class, 'edit'])->name('seasonal-prices.edit');
        Route::put('/seasonal-prices/{seasonalPrice}', [SeasonalPriceController::class, 'update'])->name('seasonal-prices.update');
        Route::delete('/seasonal-prices/{seasonalPrice}', [SeasonalPriceController::class, 'destroy'])->name('seasonal-prices.destroy');
    });

    // ===== Budgets =====
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
    });

    // ===== Reservations: Discount + Invoice =====
    Route::middleware('permission:checkin.view')->group(function () {
        Route::get('/reservations/{reservation}/invoice', [\App\Http\Controllers\ReservationController::class, 'invoice'])->name('reservations.invoice');
    });
    Route::middleware('permission:checkin.create')->group(function () {
        Route::post('/reservations/{reservation}/discount', [\App\Http\Controllers\ReservationController::class, 'applyDiscount'])->name('reservations.applyDiscount');
    });

    // ===== Leaves: Report =====
    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/leaves/report', [LeaveController::class, 'report'])->name('leaves.report');
    });
});
