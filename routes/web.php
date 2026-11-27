<?php

declare(strict_types=1);

use App\Http\Controllers\AccidentClaimController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BreakdownDriverStoppageController;
use App\Http\Controllers\CarBreakdownController;
use App\Http\Controllers\CarRegulatoryDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EquipmentBreakdownController;
use App\Http\Controllers\EquipmentDistributionController;
use App\Http\Controllers\FinancialSupportController;
use App\Http\Controllers\IdleProjectEquipmentController;
use App\Http\Controllers\MaintenanceDeviationController;
use App\Http\Controllers\OwnershipTransferController;
use App\Http\Controllers\RegistrationAmendmentController;
use App\Http\Controllers\RegulatoryDocumentController;
use App\Http\Controllers\RentalEquipmentController;
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
        Route::get('/import/template', [OwnershipTransferController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [OwnershipTransferController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [OwnershipTransferController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [OwnershipTransferController::class, 'store'])->name('store');
        Route::get('/{ownershipTransfer}', [OwnershipTransferController::class, 'show'])->name('show');
        Route::put('/{ownershipTransfer}', [OwnershipTransferController::class, 'update'])->name('update');
        Route::delete('/{ownershipTransfer}', [OwnershipTransferController::class, 'destroy'])->name('destroy');
    });

    // Module 2: سائقين متوقفين على أعطال المعدات
    Route::prefix('breakdown-stoppages')->name('breakdown-stoppages.')->group(function (): void {
        Route::get('/', [BreakdownDriverStoppageController::class, 'index'])->name('index');
        Route::get('/export', [BreakdownDriverStoppageController::class, 'export'])->name('export');
        Route::get('/import/template', [BreakdownDriverStoppageController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [BreakdownDriverStoppageController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [BreakdownDriverStoppageController::class, 'purgeAll'])->name('purge-all');
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
        Route::get('/import/template', [UnassignedDriverController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [UnassignedDriverController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [UnassignedDriverController::class, 'purgeAll'])->name('purge-all');
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
        Route::get('/import/template', [EquipmentBreakdownController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [EquipmentBreakdownController::class, 'directImport'])->name('import.direct');
        Route::post('/import/preview', [EquipmentBreakdownController::class, 'previewImport'])->name('import.preview');
        Route::post('/import/apply', [EquipmentBreakdownController::class, 'applyImport'])->name('import.apply');
        Route::post('/import/cancel', [EquipmentBreakdownController::class, 'cancelImport'])->name('import.cancel');
        Route::match(['post', 'delete'], '/purge-all', [EquipmentBreakdownController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [EquipmentBreakdownController::class, 'store'])->name('store');
        Route::get('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'show'])->name('show');
        Route::put('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'update'])->name('update');
        Route::delete('/{equipmentBreakdown}', [EquipmentBreakdownController::class, 'destroy'])->name('destroy');
    });

    // Module 5: الأوراق النظامية
    Route::prefix('regulatory-documents')->name('regulatory-documents.')->group(function (): void {
        Route::get('/', [RegulatoryDocumentController::class, 'index'])->name('index');
        Route::get('/export', [RegulatoryDocumentController::class, 'export'])->name('export');
        Route::get('/import/template', [RegulatoryDocumentController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [RegulatoryDocumentController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [RegulatoryDocumentController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [RegulatoryDocumentController::class, 'store'])->name('store');
        Route::get('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'show'])->name('show');
        Route::put('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'update'])->name('update');
        Route::delete('/{regulatoryDocument}', [RegulatoryDocumentController::class, 'destroy'])->name('destroy');
    });

    // Module 6: مخالفات وزارة النقل
    Route::prefix('transport-violations')->name('transport-violations.')->group(function (): void {
        Route::get('/', [TransportViolationController::class, 'index'])->name('index');
        Route::get('/export', [TransportViolationController::class, 'export'])->name('export');
        Route::get('/import/template', [TransportViolationController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [TransportViolationController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [TransportViolationController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [TransportViolationController::class, 'store'])->name('store');
        Route::get('/{transportViolation}', [TransportViolationController::class, 'show'])->name('show');
        Route::put('/{transportViolation}', [TransportViolationController::class, 'update'])->name('update');
        Route::delete('/{transportViolation}', [TransportViolationController::class, 'destroy'])->name('destroy');
    });

    // Module 7: نقل قطع غيار بدون تعميد الإدارة
    Route::prefix('spare-part-transfers')->name('spare-part-transfers.')->group(function (): void {
        Route::get('/', [SparePartTransferController::class, 'index'])->name('index');
        Route::get('/export', [SparePartTransferController::class, 'export'])->name('export');
        Route::get('/import/template', [SparePartTransferController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [SparePartTransferController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [SparePartTransferController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [SparePartTransferController::class, 'store'])->name('store');
        Route::get('/{sparePartTransfer}', [SparePartTransferController::class, 'show'])->name('show');
        Route::put('/{sparePartTransfer}', [SparePartTransferController::class, 'update'])->name('update');
        Route::delete('/{sparePartTransfer}', [SparePartTransferController::class, 'destroy'])->name('destroy');
    });

    // Module 8: أعطال السيارات
    Route::prefix('car-breakdowns')->name('car-breakdowns.')->group(function (): void {
        Route::get('/', [CarBreakdownController::class, 'index'])->name('index');
        Route::get('/export', [CarBreakdownController::class, 'export'])->name('export');
        Route::get('/import/template', [CarBreakdownController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [CarBreakdownController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [CarBreakdownController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [CarBreakdownController::class, 'store'])->name('store');
        Route::get('/{carBreakdown}', [CarBreakdownController::class, 'show'])->name('show');
        Route::put('/{carBreakdown}', [CarBreakdownController::class, 'update'])->name('update');
        Route::delete('/{carBreakdown}', [CarBreakdownController::class, 'destroy'])->name('destroy');
    });

    // Module 9: معدات بدون عمل فعلي بالمشاريع
    Route::prefix('idle-project-equipment')->name('idle-project-equipment.')->group(function (): void {
        Route::get('/', [IdleProjectEquipmentController::class, 'index'])->name('index');
        Route::get('/export', [IdleProjectEquipmentController::class, 'export'])->name('export');
        Route::get('/import/template', [IdleProjectEquipmentController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [IdleProjectEquipmentController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [IdleProjectEquipmentController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [IdleProjectEquipmentController::class, 'store'])->name('store');
        Route::get('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'show'])->name('show');
        Route::put('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'update'])->name('update');
        Route::delete('/{idleProjectEquipment}', [IdleProjectEquipmentController::class, 'destroy'])->name('destroy');
    });

    // Module 10: الدعم المالي (مع صفحات الشهور)
    Route::prefix('financial-support')->name('financial-support.')->group(function (): void {
        Route::get('/', [FinancialSupportController::class, 'index'])->name('index');
        Route::get('/export', [FinancialSupportController::class, 'export'])->name('export');
        Route::get('/import/template', [FinancialSupportController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [FinancialSupportController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [FinancialSupportController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [FinancialSupportController::class, 'store'])->name('store');
        Route::put('/months/{financialSupportMonth}', [FinancialSupportController::class, 'updateMonth'])->name('months.update');
        Route::patch('/months/{financialSupportMonth}/custody', [FinancialSupportController::class, 'updateMonthCustody'])->name('months.custody.update');
        Route::get('/{financialSupportTransfer}', [FinancialSupportController::class, 'show'])->name('show');
        Route::put('/{financialSupportTransfer}', [FinancialSupportController::class, 'update'])->name('update');
        Route::delete('/{financialSupportTransfer}', [FinancialSupportController::class, 'destroy'])->name('destroy');
    });

    // Module 11: انحراف الصيانات
    Route::prefix('maintenance-deviations')->name('maintenance-deviations.')->group(function (): void {
        Route::get('/', [MaintenanceDeviationController::class, 'index'])->name('index');
        Route::get('/export', [MaintenanceDeviationController::class, 'export'])->name('export');
        Route::get('/import/template', [MaintenanceDeviationController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [MaintenanceDeviationController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [MaintenanceDeviationController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [MaintenanceDeviationController::class, 'store'])->name('store');
        Route::get('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'show'])->name('show');
        Route::put('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'update'])->name('update');
        Route::delete('/{maintenanceDeviation}', [MaintenanceDeviationController::class, 'destroy'])->name('destroy');
    });

    // Module 12: معدات الإيجار
    Route::prefix('rental-equipment')->name('rental-equipment.')->group(function (): void {
        Route::get('/', [RentalEquipmentController::class, 'index'])->name('index');
        Route::get('/export', [RentalEquipmentController::class, 'export'])->name('export');
        Route::get('/import/template', [RentalEquipmentController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [RentalEquipmentController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [RentalEquipmentController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [RentalEquipmentController::class, 'store'])->name('store');
        Route::get('/{rentalEquipment}', [RentalEquipmentController::class, 'show'])->name('show');
        Route::put('/{rentalEquipment}', [RentalEquipmentController::class, 'update'])->name('update');
        Route::delete('/{rentalEquipment}', [RentalEquipmentController::class, 'destroy'])->name('destroy');
    });

    // Module 13: معدات تعديل استمارة
    Route::prefix('registration-amendments')->name('registration-amendments.')->group(function (): void {
        Route::get('/', [RegistrationAmendmentController::class, 'index'])->name('index');
        Route::get('/export', [RegistrationAmendmentController::class, 'export'])->name('export');
        Route::get('/import/template', [RegistrationAmendmentController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [RegistrationAmendmentController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [RegistrationAmendmentController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [RegistrationAmendmentController::class, 'store'])->name('store');
        Route::get('/{registrationAmendment}', [RegistrationAmendmentController::class, 'show'])->name('show');
        Route::put('/{registrationAmendment}', [RegistrationAmendmentController::class, 'update'])->name('update');
        Route::delete('/{registrationAmendment}', [RegistrationAmendmentController::class, 'destroy'])->name('destroy');
    });

    // Module 14: الأوراق النظامية للسيارات
    Route::prefix('car-regulatory-documents')->name('car-regulatory-documents.')->group(function (): void {
        Route::get('/', [CarRegulatoryDocumentController::class, 'index'])->name('index');
        Route::get('/export', [CarRegulatoryDocumentController::class, 'export'])->name('export');
        Route::get('/import/template', [CarRegulatoryDocumentController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [CarRegulatoryDocumentController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [CarRegulatoryDocumentController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [CarRegulatoryDocumentController::class, 'store'])->name('store');
        Route::get('/{carRegulatoryDocument}', [CarRegulatoryDocumentController::class, 'show'])->name('show');
        Route::put('/{carRegulatoryDocument}', [CarRegulatoryDocumentController::class, 'update'])->name('update');
        Route::delete('/{carRegulatoryDocument}', [CarRegulatoryDocumentController::class, 'destroy'])->name('destroy');
    });

    // Module 15: توزيعة المعدات
    Route::prefix('equipment-distributions')->name('equipment-distributions.')->group(function (): void {
        Route::get('/', [EquipmentDistributionController::class, 'index'])->name('index');
        Route::get('/export', [EquipmentDistributionController::class, 'export'])->name('export');
        Route::get('/import/template', [EquipmentDistributionController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [EquipmentDistributionController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [EquipmentDistributionController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [EquipmentDistributionController::class, 'store'])->name('store');
        Route::get('/{equipmentDistribution}', [EquipmentDistributionController::class, 'show'])->name('show');
        Route::put('/{equipmentDistribution}', [EquipmentDistributionController::class, 'update'])->name('update');
        Route::delete('/{equipmentDistribution}', [EquipmentDistributionController::class, 'destroy'])->name('destroy');
    });

    // Module 16: السائقين
    Route::prefix('drivers')->name('drivers.')->group(function (): void {
        Route::get('/', [DriverController::class, 'index'])->name('index');
        Route::get('/export', [DriverController::class, 'export'])->name('export');
        Route::get('/import/template', [DriverController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [DriverController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [DriverController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [DriverController::class, 'store'])->name('store');
        Route::get('/{driver}', [DriverController::class, 'show'])->name('show');
        Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
        Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    });

    // Module 17: الحوادث ومطالبة التأمين
    Route::prefix('accident-claims')->name('accident-claims.')->group(function (): void {
        Route::get('/', [AccidentClaimController::class, 'index'])->name('index');
        Route::get('/export', [AccidentClaimController::class, 'export'])->name('export');
        Route::get('/import/template', [AccidentClaimController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [AccidentClaimController::class, 'directImport'])->name('import.direct');
        Route::match(['post', 'delete'], '/purge-all', [AccidentClaimController::class, 'purgeAll'])->name('purge-all');
        Route::post('/', [AccidentClaimController::class, 'store'])->name('store');
        Route::get('/{accidentClaim}', [AccidentClaimController::class, 'show'])->name('show');
        Route::put('/{accidentClaim}', [AccidentClaimController::class, 'update'])->name('update');
        Route::delete('/{accidentClaim}', [AccidentClaimController::class, 'destroy'])->name('destroy');
    });
});
