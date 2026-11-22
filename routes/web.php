<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BreakdownDriverStoppageController;
use App\Http\Controllers\CarBreakdownController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentBreakdownController;
use App\Http\Controllers\FinancialSupportController;
use App\Http\Controllers\IdleProjectEquipmentController;
use App\Http\Controllers\MaintenanceDeviationController;
use App\Http\Controllers\OwnershipTransferController;
use App\Http\Controllers\RegulatoryDocumentController;
use App\Http\Controllers\SparePartTransferController;
use App\Http\Controllers\TransportViolationController;
use App\Http\Controllers\UnassignedDriverController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::patch('/dashboard/metrics/{key}', [DashboardController::class, 'updateMetric'])->name('dashboard.metrics.update');

    // Module 1: نقل ملكيات المعدات
    Route::prefix('ownership-transfers')->name('ownership-transfers.')->group(function (): void {
        Route::get('/', [OwnershipTransferController::class, 'index'])->name('index');
        Route::get('/export', [OwnershipTransferController::class, 'export'])->name('export');
        Route::post('/', [OwnershipTransferController::class, 'store'])->name('store');
        Route::get('/{ownershipTransfer}', [OwnershipTransferController::class, 'show'])->name('show');
        Route::put('/{ownershipTransfer}', [OwnershipTransferController::class, 'update'])->name('update');
        Route::delete('/{ownershipTransfer}', [OwnershipTransferController::class, 'destroy'])->name('destroy');
    });

    // Module 2: سائقين متوقفين على أعطال المعدات
    Route::prefix('breakdown-stoppages')->name('breakdown-stoppages.')->group(function (): void {
        Route::get('/', [BreakdownDriverStoppageController::class, 'index'])->name('index');
        Route::get('/export', [BreakdownDriverStoppageController::class, 'export'])->name('export');
        Route::post('/', [BreakdownDriverStoppageController::class, 'store'])->name('store');
        Route::get('/{breakdownStoppage}', [BreakdownDriverStoppageController::class, 'show'])->name('show');
        Route::put('/{breakdownStoppage}', [BreakdownDriverStoppageController::class, 'update'])->name('update');
        Route::delete('/{breakdownStoppage}', [BreakdownDriverStoppageController::class, 'destroy'])->name('destroy');
    });

    // Backward-compatible redirect from Phase 1 placeholder path
    Route::redirect('/breakdown-driver-stoppages', '/breakdown-stoppages', 301);

    // Module 3: سائقين بدون معدات
    Route::prefix('unassigned-drivers')->name('unassigned-drivers.')->group(function (): void {
        Route::get('/', [UnassignedDriverController::class, 'index'])->name('index');
        Route::get('/export', [UnassignedDriverController::class, 'export'])->name('export');
        Route::post('/', [UnassignedDriverController::class, 'store'])->name('store');
        Route::get('/{unassignedDriver}', [UnassignedDriverController::class, 'show'])->name('show');
        Route::put('/{unassignedDriver}', [UnassignedDriverController::class, 'update'])->name('update');
        Route::patch('/{unassignedDriver}/status', [UnassignedDriverController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{unassignedDriver}', [UnassignedDriverController::class, 'destroy'])->name('destroy');
    });

    // Module 4: أعطال المعدات
    Route::prefix('equipment-breakdowns')->name('equipment-breakdowns.')->group(function (): void {
        Route::get('/', [EquipmentBreakdownController::class, 'index'])->name('index');
        Route::get('/export', [EquipmentBreakdownController::class, 'export'])->name('export');
        Route::post('/', [EquipmentBreakdownController::class, 'store'])->name('store');
        Route::get('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'show'])->name('show');
        Route::put('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'update'])->name('update');
        Route::delete('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'destroy'])->name('destroy');
    });

    // Module 5: الأوراق النظامية
    Route::prefix('regulatory-documents')->name('regulatory-documents.')->group(function (): void {
        Route::get('/', [RegulatoryDocumentController::class, 'index'])->name('index');
        Route::get('/export', [RegulatoryDocumentController::class, 'export'])->name('export');
        Route::post('/', [RegulatoryDocumentController::class, 'store'])->name('store');
        Route::get('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'show'])->name('show');
        Route::put('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'update'])->name('update');
        Route::delete('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'destroy'])->name('destroy');
    });

    // Module 6: مخالفات وزارة النقل
    Route::prefix('transport-violations')->name('transport-violations.')->group(function (): void {
        Route::get('/', [TransportViolationController::class, 'index'])->name('index');
        Route::get('/export', [TransportViolationController::class, 'export'])->name('export');
        Route::post('/', [TransportViolationController::class, 'store'])->name('store');
        Route::get('/{transportViolation}', [TransportViolationController::class, 'show'])->name('show');
        Route::put('/{transportViolation}', [TransportViolationController::class, 'update'])->name('update');
        Route::delete('/{transportViolation}', [TransportViolationController::class, 'destroy'])->name('destroy');
    });

    // Module 7: نقل قطع غيار بدون تعميد الإدارة
    Route::prefix('spare-part-transfers')->name('spare-part-transfers.')->group(function (): void {
        Route::get('/', [SparePartTransferController::class, 'index'])->name('index');
        Route::get('/export', [SparePartTransferController::class, 'export'])->name('export');
        Route::post('/', [SparePartTransferController::class, 'store'])->name('store');
        Route::get('/{sparePartTransfer}', [SparePartTransferController::class, 'show'])->name('show');
        Route::put('/{sparePartTransfer}', [SparePartTransferController::class, 'update'])->name('update');
        Route::delete('/{sparePartTransfer}', [SparePartTransferController::class, 'destroy'])->name('destroy');
    });

    // Module 8: أعطال السيارات
    Route::prefix('car-breakdowns')->name('car-breakdowns.')->group(function (): void {
        Route::get('/', [CarBreakdownController::class, 'index'])->name('index');
        Route::get('/export', [CarBreakdownController::class, 'export'])->name('export');
        Route::post('/', [CarBreakdownController::class, 'store'])->name('store');
        Route::get('/{carBreakdown}', [CarBreakdownController::class, 'show'])->name('show');
        Route::put('/{carBreakdown}', [CarBreakdownController::class, 'update'])->name('update');
        Route::delete('/{carBreakdown}', [CarBreakdownController::class, 'destroy'])->name('destroy');
    });

    // Module 9: معدات بدون عمل فعلي بالمشاريع
    Route::prefix('idle-project-equipment')->name('idle-project-equipment.')->group(function (): void {
        Route::get('/', [IdleProjectEquipmentController::class, 'index'])->name('index');
        Route::get('/export', [IdleProjectEquipmentController::class, 'export'])->name('export');
        Route::post('/', [IdleProjectEquipmentController::class, 'store'])->name('store');
        Route::get('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'show'])->name('show');
        Route::put('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'update'])->name('update');
        Route::delete('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'destroy'])->name('destroy');
    });

    // Module 10: الدعم المالي (مع صفحات الشهور)
    Route::prefix('financial-support')->name('financial-support.')->group(function (): void {
        Route::get('/', [FinancialSupportController::class, 'index'])->name('index');
        Route::get('/export', [FinancialSupportController::class, 'export'])->name('export');
        Route::post('/', [FinancialSupportController::class, 'store'])->name('store');
        Route::put('/months/{financialSupportMonth}', [FinancialSupportController::class, 'updateMonth'])->name('months.update');
        Route::get('/{financialSupportTransfer}', [FinancialSupportController::class, 'show'])->name('show');
        Route::put('/{financialSupportTransfer}', [FinancialSupportController::class, 'update'])->name('update');
        Route::delete('/{financialSupportTransfer}', [FinancialSupportController::class, 'destroy'])->name('destroy');
    });

    // Module 11: انحراف الصيانات
    Route::prefix('maintenance-deviations')->name('maintenance-deviations.')->group(function (): void {
        Route::get('/', [MaintenanceDeviationController::class, 'index'])->name('index');
        Route::get('/export', [MaintenanceDeviationController::class, 'export'])->name('export');
        Route::post('/', [MaintenanceDeviationController::class, 'store'])->name('store');
        Route::get('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'show'])->name('show');
        Route::put('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'update'])->name('update');
        Route::delete('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'destroy'])->name('destroy');
    });
});
