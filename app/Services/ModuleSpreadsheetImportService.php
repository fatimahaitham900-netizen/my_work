<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriverStoppageStatus;
use App\Enums\OwnershipTransferStatus;
use App\Enums\RegulatoryEntityType;
use App\Enums\UnassignedDriverStatus;
use App\Models\AccidentClaim;
use App\Models\BreakdownDriverStoppage;
use App\Models\CarBreakdown;
use App\Models\CarRegulatoryDocument;
use App\Models\Driver;
use App\Models\EquipmentDistribution;
use App\Models\FinancialSupportTransfer;
use App\Models\IdleProjectEquipment;
use App\Models\MaintenanceDeviation;
use App\Models\OwnershipTransfer;
use App\Models\RegistrationAmendment;
use App\Models\RegulatoryDocument;
use App\Models\RentalEquipment;
use App\Models\SparePartTransfer;
use App\Models\TransportViolation;
use App\Models\UnassignedDriver;
use App\Support\ExtraColumns;
use App\Support\SimpleZipReader;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModuleSpreadsheetImportService
{
    /**
     * Direct full-replace import from CSV, XLSX, or XLS file into the target module table.
     *
     * @param  array<string, mixed>  $context
     * @return array{imported: int, deleted: int}
     */
    public function importDirect(string $module, UploadedFile $file, array $context = []): array
    {
        $config = $this->moduleConfig($module);
        $rawRows = $this->extractRawRows($file, $config);

        if (empty($rawRows)) {
            return ['imported' => 0, 'deleted' => 0];
        }

        // Smart auto-detection of header row among first 15 rows (works with BOTH known & fully custom flexible columns)
        [$headerRowIndex, $headerMap] = $this->detectHeaderRow($rawRows, $config['dictionary']);

        $headerRow = $rawRows[$headerRowIndex] ?? [];
        $exactHeaderMap = $this->buildExactHeaderMap($headerRow);

        if (empty($headerMap) && empty($exactHeaderMap)) {
            return ['imported' => 0, 'deleted' => 0];
        }

        $dataRows = array_slice($rawRows, $headerRowIndex + 1);
        $recordsToInsert = [];

        foreach ($dataRows as $rowValues) {
            $mapped = $this->mapRow($module, $config, $headerMap, $rowValues, $context, $exactHeaderMap);
            if ($mapped !== null) {
                $recordsToInsert[] = $mapped;
            }
        }

        if (empty($recordsToInsert)) {
            return ['imported' => 0, 'deleted' => 0];
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $tableName = (new $modelClass)->getTable();
        $tableSchema = $this->describeTableColumns($tableName);

        return DB::transaction(function () use ($modelClass, $module, $recordsToInsert, $context, $tableSchema): array {
            $deleteQuery = $modelClass::query();

            // Scoped replacement when user imports under a specific tab (e.g. Regulatory Document Type or Financial Support Month)
            if (in_array($module, ['regulatory-documents', 'car-regulatory-documents'], true) && ! empty($context['document_type'])) {
                $deleteQuery->where('document_type', (string) $context['document_type']);
            } elseif ($module === 'accident-claims' && ! empty($context['record_type'])) {
                $deleteQuery->where('record_type', (string) $context['record_type']);
            } elseif ($module === 'drivers' && ! empty($context['staff_category'])) {
                $deleteQuery->where('staff_category', (string) $context['staff_category']);
            } elseif ($module === 'financial-support' && ! empty($context['support_month'])) {
                $deleteQuery->where('support_month', (int) $context['support_month']);
                if (! empty($context['support_year'])) {
                    $deleteQuery->where('support_year', (int) $context['support_year']);
                }
            }

            $deletedCount = $deleteQuery->delete();

            foreach ($recordsToInsert as $attributes) {
                $safeAttributes = $this->sanitizeAttributesForTable($attributes, $tableSchema);
                $modelClass::query()->create($safeAttributes);
            }

            if (in_array($module, ['drivers', 'equipment-distributions'], true)) {
                app(DriverService::class)->syncBranchAndSiteFromDistributions();
            }

            DashboardService::clearCache();

            return [
                'imported' => count($recordsToInsert),
                'deleted' => $deletedCount,
            ];
        });
    }

    /**
     * Delete all active records for a module (optionally scoped by active tab context).
     *
     * @param  array<string, mixed>  $context
     */
    public function purgeAll(string $module, array $context = []): int
    {
        $config = $this->moduleConfig($module);
        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];

        return DB::transaction(static function () use ($modelClass, $module, $context): int {
            $query = $modelClass::query();

            if (in_array($module, ['regulatory-documents', 'car-regulatory-documents'], true) && ! empty($context['document_type'])) {
                $query->where('document_type', (string) $context['document_type']);
            } elseif ($module === 'accident-claims' && ! empty($context['record_type'])) {
                $query->where('record_type', (string) $context['record_type']);
            } elseif ($module === 'drivers' && ! empty($context['staff_category'])) {
                $query->where('staff_category', (string) $context['staff_category']);
            } elseif ($module === 'financial-support' && ! empty($context['support_month'])) {
                $query->where('support_month', (int) $context['support_month']);
                if (! empty($context['support_year'])) {
                    $query->where('support_year', (int) $context['support_year']);
                }
            }

            $deleted = $query->delete();
            DashboardService::clearCache();

            return $deleted;
        });
    }

    /**
     * Stream a UTF-8 BOM CSV template matching the exact columns of the module.
     */
    public function generateTemplateStream(string $module): StreamedResponse
    {
        $config = $this->moduleConfig($module);
        $headers = $config['template_headers'];
        $sampleRow = $config['template_sample'];
        $filename = $module.'-import-template.csv';

        return new StreamedResponse(static function () use ($headers, $sampleRow): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            if (! empty($sampleRow)) {
                fputcsv($handle, $sampleRow);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Per-module schema, header synonyms, field types, and CSV template definition.
     *
     * @return array<string, mixed>
     */
    protected function moduleConfig(string $module): array
    {
        return match ($module) {
            'ownership-transfers' => [
                'model' => OwnershipTransfer::class,
                'dictionary' => [
                    'equipment_code' => ['كود المعدة', 'الكود', 'كود', 'رقم المعدة', 'equipment_code', 'code'],
                    'equipment_type' => ['البيان', 'نوع المعدة', 'المعدة', 'المعده', 'equipment_type', 'type'],
                    'branch' => ['الفرع', 'الموقع', 'branch'],
                    'chassis_number' => ['رقم الهيكل', 'الشاسيه', 'الهيكل', 'chassis_number', 'chassis'],
                    'current_owner' => ['المالك الحالي', 'الجهة الحالية', 'current_owner'],
                    'target_owner' => ['المالك الجديد', 'المالك المستهدف', 'target_owner'],
                    'request_date' => ['تاريخ الطلب', 'request_date'],
                    'expected_date' => ['تاريخ الإنجاز المتوقع', 'التاريخ المتوقع', 'expected_date'],
                    'actual_date' => ['تاريخ الإنجاز الفعلي', 'تاريخ النقل', 'actual_date'],
                    'status' => ['الحالة', 'حالة النقل', 'الموقف', 'status'],
                    'blocker_reason' => ['سبب التأخير', 'العائق', 'blocker_reason'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'ملاحظة', 'notes'],
                ],
                'dates' => ['request_date', 'expected_date', 'actual_date'],
                'template_headers' => ['م', 'كود المعدة', 'البيان', 'الفرع', 'رقم الهيكل', 'الحالة', 'ملاحظات'],
                'template_sample' => ['1', 'DT_000010', 'قلاب مان', 'نجران', 'WMA123456789', 'قيد الإجراء', 'بانتظار استكمال الأوراق'],
            ],

            'breakdown-stoppages' => [
                'model' => BreakdownDriverStoppage::class,
                'dictionary' => [
                    'driver_name' => ['اسم السائق', 'السائق', 'الاسم', 'driver_name', 'driver'],
                    'employee_id' => ['رقم وظيفي', 'الرقم الوظيفي', 'employee_id'],
                    'job_title' => ['المهنة', 'الوظيفة', 'job_title'],
                    'branch' => ['الفرع', 'الموقع', 'branch'],
                    'phone' => ['الجوال', 'رقم الجوال', 'الهاتف', 'phone'],
                    'equipment_code' => ['كود المعدة', 'الكود', 'كود', 'equipment_code', 'code'],
                    'equipment_type' => ['نوع المعدة', 'المعدة', 'المعده', 'equipment_type'],
                    'stoppage_start_date' => ['آخر تشغيل', 'تاريخ التوقف', 'بداية التوقف', 'stoppage_start_date'],
                    'expected_resume_date' => ['تاريخ العودة المتوقع', 'expected_resume_date'],
                    'fault_diagnosis' => ['تشخيص العطل', 'العطل', 'وصف العطل', 'fault_diagnosis'],
                    'workshop_status' => ['موقف الورش', 'موقف الورشة', 'الموقف', 'workshop_status'],
                    'action_taken' => ['الإجراء المتخذ', 'الاجراء المتخذ', 'الإجراء', 'action_taken'],
                    'status' => ['الحالة', 'حالة السائق', 'status'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'dates' => ['stoppage_start_date', 'expected_resume_date'],
                'template_headers' => ['م', 'اسم السائق', 'رقم وظيفي', 'المهنة', 'الفرع', 'كود المعدة', 'نوع المعدة', 'آخر تشغيل', 'تشخيص العطل', 'موقف الورش', 'الإجراء المتخذ'],
                'template_sample' => ['1', 'أحمد محمد', '4512', 'سائق قلاب', 'نجران', 'DT_000015', 'قلاب', now()->subDays(10)->format('Y-m-d'), 'عطل محرك', 'قيد الإصلاح', 'متابعة الورشة'],
            ],

            'unassigned-drivers' => [
                'model' => UnassignedDriver::class,
                'dictionary' => [
                    'driver_name' => ['اسم السائق', 'السائق', 'الاسم', 'driver_name'],
                    'job_title' => ['المهنة', 'الوظيفة', 'job_title'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'location' => ['الموقع', 'الفرع', 'location', 'branch'],
                    'license_type' => ['نوع الرخصة', 'الرخصة', 'license_type'],
                    'license_expiry' => ['انتهاء الرخصة', 'تاريخ انتهاء الرخصة', 'license_expiry'],
                    'unassigned_since' => ['تاريخ التوقف', 'بدون معدة منذ', 'تاريخ البدء', 'unassigned_since'],
                    'status' => ['الحالة', 'حالة السائق', 'status'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'ملاحظة', 'notes'],
                ],
                'dates' => ['license_expiry', 'unassigned_since'],
                'template_headers' => ['م', 'اسم السائق', 'المهنة', 'الرقم الوظيفي', 'الموقع', 'ملاحظات', 'تاريخ التوقف'],
                'template_sample' => ['1', 'خالد سعيد', 'سائق شيول', '7841', 'ورشه نجران', 'جاهز للتوجيه', now()->subDays(12)->format('Y-m-d')],
            ],

            'regulatory-documents' => [
                'model' => RegulatoryDocument::class,
                'dictionary' => [
                    'entity_identifier' => ['كود المعدة', 'الكود', 'كود', 'المعرف', 'entity_identifier', 'code'],
                    'equipment_type' => ['نوع المعدة', 'المعدة', 'المعده', 'النوع', 'equipment_type'],
                    'plate_number' => ['رقم اللوحة', 'اللوحة', 'لوحة', 'plate_number', 'plate'],
                    'transport_type' => ['نوع النقل', 'النقل', 'transport_type'],
                    'branch' => ['الفرع الحالي', 'الفرع', 'الموقع', 'branch'],
                    'document_type' => ['نوع الوثيقة', 'الوثيقة', 'document_type'],
                    'document_number' => ['الرقم التسلسلي', 'رقم الوثيقة', 'رقم الكرت', 'document_number'],
                    'issue_date' => ['تاريخ الإصدار', 'تاريخ الاصدار', 'issue_date'],
                    'hijri_expiry_date' => ['انتهاء الفحص الهجري', 'انتهاء الكرت الهجري', 'انتهاء الاستمارة الهجري', 'الهجري', 'التاريخ الهجري', 'hijri_expiry_date'],
                    'expiry_date' => ['الميلادي', 'تاريخ الانتهاء', 'تاريخ انتهاء الفحص', 'تاريخ انتهاء الاستمارة', 'تاريخ انتهاء الكرت', 'expiry_date'],
                    'renewal_status' => ['الموقف من التجديد', 'موقف التجديد', 'حالة التجديد', 'الموقف', 'renewal_status'],
                    'renewal_cost' => ['تكلفة التجديد', 'المبلغ', 'renewal_cost'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'dates' => ['issue_date', 'expiry_date'],
                'decimals' => ['renewal_cost'],
                'template_headers' => ['م', 'كود المعدة', 'نوع المعدة', 'رقم اللوحة', 'نوع النقل', 'الفرع الحالي', 'الرقم التسلسلي', 'الهجري', 'الميلادي', 'الموقف من التجديد'],
                'template_sample' => ['1', 'DT_000022', 'قلاب', 'أ ب ج 1234', 'نقل عام', 'نجران', '987654321', '1447/05/10', now()->subDays(15)->format('Y-m-d'), 'جاري التجديد'],
            ],

            'transport-violations' => [
                'model' => TransportViolation::class,
                'dictionary' => [
                    'violation_number' => ['رقم المخالفة', 'رقم المخالفه', 'violation_number'],
                    'record_holder' => ['السجل', 'صاحب السجل', 'record_holder'],
                    'violation_date' => ['التاريخ', 'تاريخ المخالفة', 'تاريخ المخالفه', 'violation_date'],
                    'amount' => ['المبلغ', 'قيمة المخالفة', 'الغرامة', 'amount'],
                    'department' => ['الإدارة', 'الادارة', 'الاداره', 'department'],
                    'city' => ['مدينة المخالفة', 'المدينة', 'المدينه', 'city'],
                    'violation_description' => ['شرح المخالفة', 'وصف المخالفة', 'المخالفة', 'violation_description'],
                    'objection_status' => ['حالة الاعتراض', 'الاعتراض', 'objection_status'],
                    'plate_number' => ['رقم اللوحة', 'اللوحة', 'plate_number'],
                    'equipment_code' => ['الكود', 'كود المعدة', 'كود', 'equipment_code'],
                    'driver_name' => ['السائق', 'اسم السائق', 'driver_name'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'employee_status' => ['حالة الموظف', 'employee_status'],
                    'liability' => ['التحميل', 'المسؤولية', 'liability'],
                    'permit_status' => ['موقف التصريح', 'التصريح', 'permit_status'],
                ],
                'dates' => ['violation_date'],
                'decimals' => ['amount'],
                'template_headers' => ['م', 'رقم المخالفة', 'السجل', 'التاريخ', 'المبلغ', 'الإدارة', 'مدينة المخالفة', 'شرح المخالفة', 'حالة الاعتراض', 'رقم اللوحة', 'الكود', 'السائق', 'الرقم الوظيفي', 'حالة الموظف', 'التحميل', 'موقف التصريح'],
                'template_sample' => ['1', 'VIO-9001', 'شركة المقاولات', now()->format('Y-m-d'), '3000', 'النقل', 'الرياض', 'تجاوز الحمولة المقررة', 'بانتظار الرد', 'د هـ و 5544', 'TR_000012', 'محمد علي', '3310', 'على رأس العمل', 'على السائق', 'ساري'],
            ],

            'spare-part-transfers' => [
                'model' => SparePartTransfer::class,
                'dictionary' => [
                    'item_description' => ['البند', 'القطعة', 'القطعه', 'اسم القطعة', 'وصف البند', 'item_description'],
                    'from_equipment' => ['المعدة المنقول منها', 'المنقول منها', 'من معدة', 'from_equipment'],
                    'to_equipment' => ['المعدة المنقول إليها', 'المعدة المنقول اليها', 'المنقول إليها', 'إلى معدة', 'to_equipment'],
                    'transfer_date' => ['التاريخ', 'تاريخ النقل', 'transfer_date'],
                    'notes' => ['ملاحظة', 'ملاحظات', 'الملاحظات', 'notes'],
                ],
                'dates' => ['transfer_date'],
                'template_headers' => ['م', 'البند', 'المعدة المنقول منها', 'المعدة المنقول إليها', 'التاريخ', 'ملاحظة'],
                'template_sample' => ['1', 'طلمبة هيدروليك', 'EX_000014', 'EX_000022', now()->format('Y-m-d'), 'تم النقل بدون تعميد مسبق'],
            ],

            'car-breakdowns' => [
                'model' => CarBreakdown::class,
                'dictionary' => [
                    'car_code' => ['كود السيارة', 'كود المركبة', 'الكود', 'كود', 'car_code', 'code'],
                    'vehicle_category' => ['التصنيف', 'سيارة / باص', 'سياره / باص', 'نوع المركبة', 'vehicle_category', 'category'],
                    'car_type' => ['نوع السيارة', 'السيارة', 'السياره', 'النوع', 'car_type'],
                    'plate_number' => ['اللوحة', 'رقم اللوحة', 'لوحة', 'plate_number'],
                    'model_year' => ['موديل', 'الموديل', 'سنة الصنع', 'model_year', 'model'],
                    'previous_receiver' => ['المستلم السابق', 'المستلم', 'السائق', 'previous_receiver'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'department' => ['الإدارة', 'الادارة', 'القسم', 'department'],
                    'job_title' => ['الوظيفة', 'المهنة', 'job_title'],
                    'fuel_type' => ['نوع الوقود', 'الوقود', 'fuel_type'],
                    'branch' => ['الفرع', 'الموقع', 'branch'],
                    'condition_status' => ['الحالة', 'حالة السيارة', 'العطل', 'الموقف', 'condition_status'],
                ],
                'template_headers' => ['م', 'كود السيارة', 'التصنيف', 'نوع السيارة', 'اللوحة', 'موديل', 'المستلم السابق', 'الرقم الوظيفي', 'الإدارة', 'الوظيفة', 'نوع الوقود', 'الفرع', 'الحالة'],
                'template_sample' => ['1', 'CAR-045', 'سيارة', 'تويوتا هايلوكس', 'ر س ت 4411', '2022', 'سالم ناصر', '2190', 'المشاريع', 'مراقب موقع', 'بنزين', 'نجران', 'تحتاج عمرة ماكينة'],
            ],

            'idle-project-equipment' => [
                'model' => IdleProjectEquipment::class,
                'dictionary' => [
                    'equipment_code' => ['كود المعدة', 'الكود', 'كود', 'equipment_code', 'code'],
                    'equipment_type' => ['نوع المعدة', 'المعدة', 'المعده', 'النوع', 'equipment_type'],
                    'driver_name' => ['اسم السائق', 'السائق', 'الاسم', 'driver_name'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'branch' => ['الفرع', 'branch'],
                    'site_location' => ['الموقع', 'المشروع', 'موقع العمل', 'site_location'],
                    'equipment_status' => ['حالة المعدة', 'الحالة', 'الموقف', 'equipment_status'],
                    'total_idle_days' => ['إجمالي أيام التوقف', 'اجمالي ايام التوقف', 'أيام التوقف', 'ايام التوقف', 'total_idle_days'],
                ],
                'integers' => ['total_idle_days'],
                'template_headers' => ['م', 'كود المعدة', 'نوع المعدة', 'اسم السائق', 'الرقم الوظيفي', 'الفرع', 'الموقع', 'حالة المعدة', 'إجمالي أيام التوقف'],
                'template_sample' => ['1', 'WL_000018', 'شيول', 'عمر صالح', '6120', 'تبوك', 'مشروع الطريق الدائري', 'بدون عمل فعلي', '14'],
            ],

            'financial-support' => [
                'model' => FinancialSupportTransfer::class,
                'dictionary' => [
                    'support_year' => ['السنة', 'العام', 'support_year', 'year'],
                    'support_month' => ['الشهر', 'رقم الشهر', 'support_month', 'month'],
                    'equipment_type' => ['نوع المعدة', 'المعدة', 'المعده', 'equipment_type'],
                    'equipment_code' => ['الكود', 'كود المعدة', 'كود', 'equipment_code', 'code'],
                    'amount' => ['المبلغ', 'القيمة', 'التكلفة', 'amount'],
                    'beneficiary_name' => ['اسم المستفيد', 'المستفيد', 'المورد', 'beneficiary_name'],
                    'fault_type' => ['نوع العطل', 'العطل', 'الوصف', 'fault_type'],
                    'branch' => ['الفرع', 'الموقع', 'branch'],
                    'case_status' => ['الحالة', 'حالة الطلب', 'case_status'],
                    'po_number' => ['رقم الـ PO / المرحلة', 'رقم الـ PO', 'رقم الطلب', 'PO', 'po_number'],
                    'transfer_stage' => ['مرحلة التحويل', 'المرحلة', 'transfer_stage'],
                    'transfer_date' => ['تاريخ التحويل', 'transfer_date'],
                    'supply_date' => ['تاريخ التوريد', 'supply_date'],
                    'repair_date' => ['تاريخ الإصلاح', 'تاريخ الاصلاح', 'repair_date'],
                    'repair_status' => ['موقف الإصلاح', 'موقف الاصلاح', 'حالة الإصلاح', 'repair_status'],
                ],
                'integers' => ['support_year', 'support_month'],
                'decimals' => ['amount'],
                'template_headers' => ['م', 'الشهر', 'نوع المعدة', 'الكود', 'المبلغ', 'اسم المستفيد', 'نوع العطل', 'الفرع', 'الحالة', 'رقم الـ PO', 'مرحلة التحويل', 'تاريخ التحويل', 'تاريخ التوريد', 'تاريخ الإصلاح', 'موقف الإصلاح'],
                'template_sample' => ['1', (string) now()->month, 'مولد', 'GN_000058', '10695', 'مؤسسة قطع الغيار', 'عطل كهرباء', 'نجران', 'معتمد', 'PO-2026-101', 'تم التحويل', now()->format('Y-m-d'), '', '', 'قيد الإصلاح'],
            ],

            'maintenance-deviations' => [
                'model' => MaintenanceDeviation::class,
                'dictionary' => [
                    'day_number' => ['التاريخ', 'اليوم', 'رقم اليوم', 'day_number', 'day'],
                    'daily_maintenance_rate' => ['نسبة الصيانة اليومية', 'الصيانة اليومية', 'daily_maintenance_rate'],
                    'preventive_maintenance_rate' => ['نسبة الصيانة الوقائية', 'الصيانة الوقائية', 'preventive_maintenance_rate'],
                    'daily_deviation_rate' => ['نسبة انحراف الصيانة اليومية', 'انحراف الصيانة اليومية', 'daily_deviation_rate'],
                    'preventive_deviation_rate' => ['نسبة انحراف الصيانة الوقائية', 'انحراف الصيانة الوقائية', 'preventive_deviation_rate'],
                    'status_note' => ['الحالة', 'ملاحظة الحالة', 'البيان', 'status_note'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'integers' => ['day_number'],
                'decimals' => ['daily_maintenance_rate', 'preventive_maintenance_rate', 'daily_deviation_rate', 'preventive_deviation_rate'],
                'template_headers' => ['التاريخ', 'نسبة الصيانة اليومية', 'نسبة الصيانة الوقائية', 'نسبة انحراف الصيانة اليومية', 'نسبة انحراف الصيانة الوقائية', 'ملاحظات'],
                'template_sample' => ['1', '99%', '75%', '1%', '25%', ''],
            ],

            'rental-equipment' => [
                'model' => RentalEquipment::class,
                'dictionary' => [
                    'contractor_name' => ['اسم المقاول', 'المقاول', 'الشركة', 'المؤسسة', 'contractor_name', 'contractor'],
                    'equipment_type' => ['النوع', 'نوع المعدة', 'نوع المعده', 'المعدة', 'equipment_type', 'type'],
                    'rental_amount' => ['مبلغ الايجار', 'مبلغ الإيجار', 'الايجار', 'الإيجار', 'المبلغ', 'قيمة الايجار', 'قيمة الإيجار', 'rental_amount', 'amount'],
                    'rental_type' => ['نوع الايجار', 'نوع الإيجار', 'مدة الايجار', 'مدة الإيجار', 'نظام الايجار', 'rental_type'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'المنطقة', 'branch'],
                    'project' => ['المشروع', 'الموقع', 'الموقع / المشروع', 'اسم المشروع', 'project'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'decimals' => ['rental_amount'],
                'template_headers' => ['م', 'اسم المقاول', 'النوع', 'مبلغ الايجار', 'نوع الايجار', 'الفرع', 'المشروع'],
                'template_sample' => ['1', 'شركة ابن الجزيرة المحدودة', 'حفار', '16000', 'شهري', 'الرياض', 'الحائر'],
            ],

            'registration-amendments' => [
                'model' => RegistrationAmendment::class,
                'dictionary' => [
                    'equipment_code' => ['الكود', 'كود المعدة', 'كود المعده', 'كود', 'equipment_code', 'code'],
                    'equipment_type' => ['نوع المعدة', 'نوع المعده', 'المعدة', 'المعده', 'النوع', 'equipment_type', 'type'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'الموقع', 'branch'],
                    'stoppage_date' => ['تاريخ التوقف', 'تاريخ التوقيف', 'التاريخ', 'stoppage_date', 'date'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'dates' => ['stoppage_date'],
                'template_headers' => ['م', 'الكود', 'نوع المعدة', 'الفرع', 'تاريخ التوقف'],
                'template_sample' => ['1', 'DT_000033', 'قلاب', 'نجران', now()->subDays(10)->format('Y-m-d')],
            ],

            'car-regulatory-documents' => [
                'model' => CarRegulatoryDocument::class,
                'dictionary' => [
                    'document_type' => ['نوع الوثيقة', 'الوثيقة', 'document_type'],
                    'car_code' => ['كود السيارة', 'كود السياره', 'الكود', 'كود', 'car_code', 'code'],
                    'plate_number' => ['رقم اللوحة', 'رقم اللوحه', 'اللوحة', 'اللوحه', 'plate_number'],
                    'car_type_model' => ['نوع السيارة والموديل', 'نوع السياره والموديل', 'النوع والموديل', 'نوع السيارة', 'السيارة', 'car_type_model'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'branch'],
                    'project_location' => ['المشروع / الموقع', 'المشروع', 'الموقع', 'project_location', 'location'],
                    'hijri_expiry_date' => ['تاريخ الانتهاء (هجري)', 'تاريخ الانتهاء هجري', 'التاريخ الهجري', 'هجري', 'hijri_expiry_date'],
                    'expiry_date' => ['تاريخ الانتهاء (ميلادي)', 'تاريخ الانتهاء ميلادي', 'تاريخ الانتهاء', 'الميلادي', 'ميلادي', 'expiry_date'],
                    'status_label' => ['الأيام وحالة الفحص الدوري', 'الأيام وحالة الاستمارة', 'الأيام وحالة التأمين', 'الأيام والحالة', 'الحالة', 'status_label'],
                    'notes' => ['ملاحظات', 'الملاحظات', 'notes'],
                ],
                'dates' => ['expiry_date'],
                'template_headers' => ['م', 'كود السيارة', 'رقم اللوحة', 'نوع السيارة والموديل', 'الفرع', 'المشروع / الموقع', 'تاريخ الانتهاء (هجري)', 'تاريخ الانتهاء (ميلادي)'],
                'template_sample' => ['1', 'C_000205', 'أ ل ر 9882', 'تويوتا هايلكس غمارتين (2018)', 'أبها', 'مشروع باللحمر', '1447-10-21', now()->subDays(30)->format('Y-m-d')],
            ],

            'equipment-distributions' => [
                'model' => EquipmentDistribution::class,
                'dictionary' => [
                    'equipment_code' => ['كود المعدة', 'كود المعده', 'الكود', 'كود', 'رقم المعدة', 'equipment_code', 'code'],
                    'equipment_type' => ['نوع المعدة', 'نوع المعده', 'المعدة', 'المعده', 'النوع', 'equipment_type', 'type'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'المنطقة', 'branch'],
                    'site_location' => ['الموقع', 'المشروع', 'المشروع / الموقع', 'موقع العمل', 'site_location', 'location'],
                    'equipment_status' => ['حالة المعدة', 'حالة المعده', 'الحالة', 'الحاله', 'الموقف', 'equipment_status', 'status'],
                    'notes' => ['ملاحظه', 'ملاحظة', 'ملاحظات', 'الملاحظات', 'notes'],
                ],
                'template_headers' => ['نوع المعدة', 'الفرع', 'الموقع', 'حالة المعدة', 'ملاحظه'],
                'template_sample' => ['حفارات', 'نجران', 'تشغيل نجران', 'تم تشغيلها ساعة أو أكثر', 'بدون عمل فعلي'],
            ],

            'drivers' => [
                'model' => Driver::class,
                'dictionary' => [
                    'equipment_code' => ['كود المعدة', 'كود المعده', 'الكود', 'كود', 'رقم المعدة', 'equipment_code', 'code'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'المنطقة', 'branch'],
                    'site_location' => ['الموقع', 'المشروع', 'المشروع / الموقع', 'site_location', 'location'],
                    'driver_name' => ['اسم السائق', 'السائق', 'الاسم', 'driver_name', 'name'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'job_title' => ['المهنة', 'المهنه', 'الوظيفة', 'الوظيفه', 'job_title'],
                    'nationality' => ['الجنسية', 'الجنسيه', 'nationality'],
                    'salary' => ['الراتب', 'الراتب الإجمالي', 'الراتب الاجمالي', 'إجمالي الراتب', 'المرتب', 'الأجر', 'salary'],
                    'sponsor' => ['الكفيل', 'الكفالة', 'الكفاله', 'sponsor'],
                    'status' => ['الحالة', 'الحاله', 'حالة الموظف', 'status'],
                    'notes' => ['ملاحظات', 'ملاحظة', 'ملاحظه', 'notes'],
                ],
                'decimals' => ['salary'],
                'template_headers' => ['كود المعدة', 'الفرع', 'الموقع', 'اسم السائق', 'الرقم الوظيفي', 'المهنة', 'الجنسية', 'الراتب', 'الكفيل'],
                'template_sample' => ['AP_000008', '', '', 'خالد حسن عنقاد', '5141', 'سائق فرادة اسفلت', 'Yemen', '4051', 'كفاله شركة'],
            ],

            'accident-claims' => [
                'model' => AccidentClaim::class,
                'dictionary' => [
                    'vehicle_code' => ['كود السيارة', 'كود السياره', 'كود المعدة', 'كود المركبة', 'الكود', 'كود', 'vehicle_code', 'code'],
                    'user_code' => ['كود المستخدم', 'رقم المستخدم', 'المستخدم', 'user_code'],
                    'plate_number' => ['رقم اللوحة', 'اللوحة', 'رقم اللوحه', 'plate_number', 'plate'],
                    'accident_date' => ['تاريخ الحادث', 'التاريخ', 'accident_date', 'date'],
                    'accident_location' => ['موقع الحادث', 'الموقع', 'المكان', 'accident_location', 'location'],
                    'branch' => ['الفرع', 'الفرع الحالي', 'branch'],
                    'record_holder' => ['السجل', 'صاحب السجل', 'record_holder'],
                    'driver_name' => ['السائق', 'اسم السائق', 'driver_name', 'driver'],
                    'employee_id' => ['الرقم الوظيفي', 'رقم وظيفي', 'employee_id'],
                    'najm_report_number' => ['رقم تقرير نجم', 'تقرير نجم', 'رقم نجم', 'najm_report_number'],
                    'claim_number' => ['رقم المطالبة', 'رقم المطالبه', 'المطالبة', 'claim_number'],
                    'insurance_company' => ['شركة التأمين', 'شركة التامين', 'التأمين', 'insurance_company'],
                    'fault_percentage' => ['نسبة المسؤولية', 'نسبة الخطأ', 'المسؤولية', 'fault_percentage'],
                    'estimated_cost' => ['التكلفة', 'التكلفة التقديرية', 'التقدير', 'estimated_cost', 'cost'],
                    'compensation_amount' => ['التعويض', 'مبلغ التعويض', 'قيمة التعويض', 'compensation_amount'],
                    'claim_status' => ['حالة الحادث', 'حالة المطالبة', 'الحالة', 'الموقف', 'claim_status', 'status'],
                    'notes' => ['ملاحظات', 'ملاحظة', 'ملاحظه', 'notes'],
                ],
                'dates' => ['accident_date'],
                'integers' => ['fault_percentage'],
                'decimals' => ['estimated_cost', 'compensation_amount'],
                'template_headers' => ['م', 'كود المستخدم', 'الفرع', 'السجل', 'ملاحظات'],
                'template_sample' => ['1', 'C_000120', 'الرياض', 'شركة المقاولات', 'حادث بسيط تم التقييم'],
            ],

            default => throw new InvalidArgumentException("Unsupported import module: {$module}"),
        };
    }

    /**
     * Build map of unmapped/new column indexes to their original header labels.
     *
     * @param  list<string>  $headers
     * @param  array<int, string>  $knownHeaderMap
     * @return array<int, string>
     */
    protected function buildExtraHeaderMap(array $headers, array $knownHeaderMap): array
    {
        $extraMap = [];
        $usedLabels = [];
        $ignoredHeaders = ['م', 'م.', '#', 'ت', 'no', 'no.', 'sn', 's/n', 'id', 'تسلسل'];

        foreach ($headers as $colIndex => $rawHeader) {
            if (isset($knownHeaderMap[$colIndex])) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', (string) $rawHeader) ?? '');
            if ($label === '') {
                continue;
            }

            $normalized = mb_strtolower($this->cleanHeaderString($label));
            if ($normalized === '' || in_array($normalized, $ignoredHeaders, true)) {
                continue;
            }

            // Ensure unique key if duplicate extra header names exist in file
            $uniqueLabel = $label;
            $suffix = 2;
            while (isset($usedLabels[$uniqueLabel])) {
                $uniqueLabel = "{$label} ({$suffix})";
                $suffix++;
            }

            $usedLabels[$uniqueLabel] = true;
            $extraMap[$colIndex] = $uniqueLabel;
        }

        return $extraMap;
    }

    /**
     * Collect every non-empty header column in the exact order it appears in the uploaded spreadsheet.
     *
     * @param  list<string>  $headers
     * @return array<int, string>
     */
    protected function buildExactHeaderMap(array $headers): array
    {
        $exactMap = [];
        $usedLabels = [];

        foreach ($headers as $colIndex => $rawHeader) {
            $label = trim(preg_replace('/\s+/u', ' ', (string) $rawHeader) ?? '');
            if ($label === '') {
                continue;
            }

            $uniqueLabel = $label;
            $suffix = 2;
            while (isset($usedLabels[$uniqueLabel])) {
                $uniqueLabel = "{$label} ({$suffix})";
                $suffix++;
            }

            $usedLabels[$uniqueLabel] = true;
            $exactMap[$colIndex] = $uniqueLabel;
        }

        return $exactMap;
    }

    /**
     * Map a raw spreadsheet row into model attributes for a specific module.
     *
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $headerMap
     * @param  list<string>  $row
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $exactHeaderMap
     * @return array<string, mixed>|null
     */
    protected function mapRow(string $module, array $config, array $headerMap, array $row, array $context, array $exactHeaderMap = []): ?array
    {
        $raw = [];
        foreach ($headerMap as $colIndex => $attrKey) {
            $cellValue = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
            if (! isset($raw[$attrKey]) || ($raw[$attrKey] === '' && $cellValue !== '')) {
                $raw[$attrKey] = $cellValue;
            }
        }

        $dateFields = $config['dates'] ?? [];
        $exactColumns = [];
        foreach ($exactHeaderMap as $colIndex => $exactLabel) {
            $val = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
            if ($val === '' || $val === '—' || $val === '-') {
                $exactColumns[$exactLabel] = '';

                continue;
            }

            $mappedAttr = $headerMap[$colIndex] ?? null;
            if ($mappedAttr !== null && in_array($mappedAttr, $dateFields, true) && is_numeric($val)) {
                $parsed = $this->parseDate($val);
                $exactColumns[$exactLabel] = $parsed ?? $val;
            } else {
                $exactColumns[$exactLabel] = $val;
            }
        }

        // Skip completely empty rows
        $nonEmptyValues = array_filter($raw, static fn (string $v): bool => $v !== '' && $v !== '—' && $v !== '-');
        $nonEmptyExact = array_filter($exactColumns, static fn (string $v): bool => $v !== '');
        if (empty($nonEmptyValues) && empty($nonEmptyExact)) {
            return null;
        }

        // Skip summary/total rows
        $firstCell = trim((string) ($row[0] ?? ''));
        if (str_contains($firstCell, 'الإجمالي') || str_contains($firstCell, 'الاجمالى') || str_contains($firstCell, 'الاجمالي') || strtolower($firstCell) === 'total') {
            return null;
        }

        $dateFields = $config['dates'] ?? [];
        $decimalFields = $config['decimals'] ?? [];
        $intFields = $config['integers'] ?? [];

        $attributes = [];
        foreach ($raw as $key => $value) {
            if ($value === '' || $value === '—' || $value === '-') {
                $attributes[$key] = null;

                continue;
            }

            if (in_array($key, $dateFields, true)) {
                $attributes[$key] = $this->parseDate($value);
            } elseif (in_array($key, $decimalFields, true)) {
                $attributes[$key] = $this->parseDecimal($value);
            } elseif (in_array($key, $intFields, true)) {
                $attributes[$key] = $this->parseInt($value);
            } else {
                $attributes[$key] = $value;
            }
        }

        // Module-specific normalizations & defaults
        if ($module === 'ownership-transfers') {
            $rawStatus = (string) ($raw['status'] ?? '');
            $attributes['status'] = match (true) {
                str_contains($rawStatus, 'متأخر') || str_contains($rawStatus, 'متاخر') || str_contains($rawStatus, 'مؤجل') || strtolower($rawStatus) === 'delayed' => OwnershipTransferStatus::Delayed->value,
                str_contains($rawStatus, 'مكتمل') || str_contains($rawStatus, 'تم النقل') || str_contains($rawStatus, 'منتهي') || strtolower($rawStatus) === 'completed' => OwnershipTransferStatus::Completed->value,
                default => OwnershipTransferStatus::Pending->value,
            };
        } elseif ($module === 'breakdown-stoppages') {
            $rawStatus = (string) ($raw['status'] ?? '');
            $attributes['status'] = match (true) {
                str_contains($rawStatus, 'تمت المعالجة') || str_contains($rawStatus, 'عاد') || str_contains($rawStatus, 'مكتمل') || strtolower($rawStatus) === 'resolved' => DriverStoppageStatus::Resolved->value,
                str_contains($rawStatus, 'إعادة توجيه') || str_contains($rawStatus, 'توجيه') || strtolower($rawStatus) === 'reassigned' => DriverStoppageStatus::Reassigned->value,
                default => DriverStoppageStatus::Waiting->value,
            };
        } elseif ($module === 'unassigned-drivers') {
            $rawStatus = (string) ($raw['status'] ?? '');
            $attributes['status'] = match (true) {
                str_contains($rawStatus, 'مكلف') || str_contains($rawStatus, 'تم التكليف') || strtolower($rawStatus) === 'assigned' => UnassignedDriverStatus::Assigned->value,
                str_contains($rawStatus, 'تقييم') || strtolower($rawStatus) === 'evaluation' => UnassignedDriverStatus::Evaluation->value,
                default => UnassignedDriverStatus::Ready->value,
            };
        } elseif ($module === 'regulatory-documents') {
            $attributes['entity_type'] = RegulatoryEntityType::Equipment->value;
            if (empty($attributes['document_type'])) {
                $attributes['document_type'] = ! empty($context['document_type']) ? (string) $context['document_type'] : 'استمارة';
            }
            $expiryCarbon = ! empty($attributes['expiry_date']) ? Carbon::parse((string) $attributes['expiry_date']) : null;
            $attributes['compliance_status'] = RegulatoryDocument::resolveComplianceStatus($expiryCarbon)->value;
            $attributes['renewal_cost'] = $attributes['renewal_cost'] ?? 0;
        } elseif ($module === 'financial-support') {
            $attributes['support_year'] = $attributes['support_year'] ?? (int) ($context['support_year'] ?? now()->year);
            $attributes['support_month'] = $attributes['support_month'] ?? (int) ($context['support_month'] ?? now()->month);
            $attributes['amount'] = $attributes['amount'] ?? 0;

            // Convert Excel serial dates in string date fields (transfer_date, supply_date, repair_date)
            foreach (['transfer_date', 'supply_date', 'repair_date'] as $strDateCol) {
                if (! empty($attributes[$strDateCol])) {
                    $parsed = $this->parseDate((string) $attributes[$strDateCol]);
                    if ($parsed !== null) {
                        $attributes[$strDateCol] = $parsed;
                    }
                }
            }
        } elseif ($module === 'maintenance-deviations') {
            $rawNote = (string) ($raw['preventive_deviation_rate'] ?? '').' '.(string) ($raw['status_note'] ?? '');
            if (str_contains($rawNote, 'بدون انحراف') || str_contains($rawNote, 'مستوى الصيانة ممتاز')) {
                $attributes['row_type'] = 'no_deviations';
                $attributes['status_note'] = trim((string) ($raw['status_note'] ?: $raw['preventive_deviation_rate']));
                $attributes['preventive_deviation_rate'] = null;
            } elseif (str_contains($rawNote, 'إجازة') || str_contains($rawNote, 'اجازة') || str_contains($rawNote, 'جمعة') || str_contains($rawNote, 'عطلة')) {
                $attributes['row_type'] = 'holiday';
            } elseif ($attributes['daily_maintenance_rate'] === null && $attributes['preventive_maintenance_rate'] === null && $attributes['daily_deviation_rate'] === null && $attributes['preventive_deviation_rate'] === null) {
                $attributes['row_type'] = 'holiday';
            } else {
                $attributes['row_type'] = 'active';
            }
        } elseif ($module === 'car-regulatory-documents') {
            if (empty($attributes['document_type'])) {
                $attributes['document_type'] = ! empty($context['document_type'])
                    ? (string) $context['document_type']
                    : CarRegulatoryDocument::TYPE_INSPECTION;
            }
        } elseif ($module === 'car-breakdowns') {
            $attributes['vehicle_category'] = CarBreakdown::resolveCategory(
                isset($attributes['vehicle_category']) ? (string) $attributes['vehicle_category'] : null,
                isset($attributes['car_code']) ? (string) $attributes['car_code'] : null,
                isset($attributes['car_type']) ? (string) $attributes['car_type'] : null,
            );
        } elseif ($module === 'idle-project-equipment') {
            $attributes['total_idle_days'] = $attributes['total_idle_days'] ?? 0;
        } elseif ($module === 'rental-equipment') {
            $hasDescriptor = trim((string) ($attributes['contractor_name'] ?? '')) !== ''
                || trim((string) ($attributes['equipment_type'] ?? '')) !== ''
                || trim((string) ($attributes['rental_type'] ?? '')) !== ''
                || trim((string) ($attributes['branch'] ?? '')) !== ''
                || trim((string) ($attributes['project'] ?? '')) !== '';

            // Only skip Excel footer SUM rows that contain a single isolated numeric total and no other columns
            if (! empty($headerMap) && ! $hasDescriptor && count($nonEmptyExact) <= 1) {
                return null;
            }

            $attributes['rental_amount'] = $attributes['rental_amount'] ?? 0;
        } elseif ($module === 'transport-violations') {
            $attributes['amount'] = $attributes['amount'] ?? 0;
        } elseif ($module === 'drivers') {
            $attributes['staff_category'] = ! empty($context['staff_category'])
                ? (string) $context['staff_category']
                : Driver::CATEGORY_DRIVER;
            $attributes['salary'] = $attributes['salary'] ?? 0;
            if ($attributes['staff_category'] === Driver::CATEGORY_WORKSHOP_STAFF && empty($attributes['status'])) {
                $attributes['status'] = 'نشط';
            }
        } elseif ($module === 'accident-claims') {
            $attributes['record_type'] = ! empty($context['record_type'])
                ? (string) $context['record_type']
                : AccidentClaim::TYPE_ACCIDENT;
            $attributes['estimated_cost'] = $attributes['estimated_cost'] ?? 0;
            $attributes['compensation_amount'] = $attributes['compensation_amount'] ?? 0;
        }

        if (! empty($exactHeaderMap)) {
            $attributes['extra_columns'] = [
                ExtraColumns::EXACT_KEY => $exactColumns,
            ];
        }

        return $attributes;
    }

    /**
     * Extract raw rows from CSV, XLSX, or XLS uploaded file.
     *
     * @param  array<string, mixed>  $config
     * @return list<list<string>>
     */
    protected function extractRawRows(UploadedFile $file, array $config): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($path === false) {
            return [];
        }

        // Detect actual XLSX ZIP magic bytes regardless of file extension (handles renamed .xls/.xlsx on hosting)
        $magic = @file_get_contents($path, false, null, 0, 4);
        if ($magic === "PK\x03\x04") {
            return $this->parseXlsx($path, $config['dictionary']);
        }

        return match ($extension) {
            'xlsx' => $this->parseXlsx($path, $config['dictionary']),
            'xls' => $this->parseXls($path, $config['dictionary']),
            default => $this->parseCsv($path),
        };
    }

    /**
     * Detect the actual header row among the first 15 rows.
     * Supports both dictionary-matched sheets and completely custom/flexible column layouts
     * (even when Row 0 is a single-cell merged title banner).
     *
     * @param  list<list<string>>  $rawRows
     * @param  array<string, list<string>>  $dictionary
     * @return array{0: int, 1: array<int, string>}
     */
    protected function detectHeaderRow(array $rawRows, array $dictionary): array
    {
        $bestIdx = 0;
        $bestMap = [];
        $bestScore = -1;

        foreach (array_slice($rawRows, 0, 15, true) as $idx => $candidateRow) {
            $candidateMap = $this->buildHeaderMap($candidateRow, $dictionary);
            $matchCount = count($candidateMap);

            $textHeadersCount = 0;
            foreach ($candidateRow as $cell) {
                $trimmed = trim((string) $cell);
                if ($trimmed !== '' && ! is_numeric(str_replace([',', '%', '.', '-', '/'], '', $trimmed))) {
                    $textHeadersCount++;
                }
            }

            // Dictionary matches weigh heavily; when custom/flexible columns don't match the dictionary,
            // prefer the first multi-column text header row over a 1-cell title banner.
            $score = ($matchCount * 100) + ($textHeadersCount >= 2 ? (10 + $textHeadersCount) : $textHeadersCount);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIdx = (int) $idx;
                $bestMap = $candidateMap;
            }
        }

        return [$bestIdx, $bestMap];
    }

    /**
     * Inspect table columns once per import to guarantee 100% MySQL/MariaDB strict-mode compatibility.
     *
     * @return array<string, array{nullable: bool, has_default: bool, max_length: int|null, type_name: string}>
     */
    protected function describeTableColumns(string $table): array
    {
        $schema = [];

        try {
            foreach (Schema::getColumns($table) as $col) {
                $name = (string) ($col['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $type = strtolower((string) ($col['type'] ?? ''));
                $typeName = strtolower((string) ($col['type_name'] ?? $type));
                $maxLength = null;

                if (preg_match('/(?:var)?char\((\d+)\)/i', $type, $m)) {
                    $maxLength = (int) $m[1];
                }

                $schema[$name] = [
                    'nullable' => (bool) ($col['nullable'] ?? true),
                    'has_default' => ($col['default'] ?? null) !== null,
                    'max_length' => $maxLength,
                    'type_name' => $typeName,
                ];
            }
        } catch (\Throwable) {
            return [];
        }

        return $schema;
    }

    /**
     * Ensure every attribute is safe for MySQL/MariaDB strict mode:
     * - Drops unknown DB columns (full values are already preserved in extra_columns)
     * - Never sends explicit NULL to a NOT NULL column (lets DB default apply or sets safe zero/empty)
     * - Prevents VARCHAR length overflow (1406 Data too long) while keeping full value in extra_columns
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{nullable: bool, has_default: bool, max_length: int|null, type_name: string}>  $tableSchema
     * @return array<string, mixed>
     */
    protected function sanitizeAttributesForTable(array $attributes, array $tableSchema): array
    {
        if (empty($tableSchema)) {
            return $attributes;
        }

        $clean = [];

        foreach ($attributes as $key => $value) {
            if (! isset($tableSchema[$key])) {
                continue;
            }

            $meta = $tableSchema[$key];

            if ($value === null) {
                if (! $meta['nullable']) {
                    if ($meta['has_default']) {
                        // Omit so MySQL/MariaDB uses the column's DEFAULT value
                        continue;
                    }

                    $typeName = $meta['type_name'];
                    if (str_contains($typeName, 'int') || str_contains($typeName, 'dec') || str_contains($typeName, 'num') || str_contains($typeName, 'float') || str_contains($typeName, 'double')) {
                        $value = 0;
                    } elseif ($typeName === 'date') {
                        $value = now()->format('Y-m-d');
                    } else {
                        $value = '';
                    }
                }
            } elseif (is_string($value) && $meta['max_length'] !== null && mb_strlen($value, 'UTF-8') > $meta['max_length']) {
                $value = mb_substr($value, 0, $meta['max_length'], 'UTF-8');
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * Map spreadsheet header row to model attribute keys.
     *
     * @param  list<string>  $headers
     * @param  array<string, list<string>>  $dictionary
     * @return array<int, string>
     */
    protected function buildHeaderMap(array $headers, array $dictionary): array
    {
        $map = [];

        foreach ($headers as $colIndex => $rawHeader) {
            $normalized = $this->cleanHeaderString($rawHeader);
            if ($normalized === '' || $normalized === 'م' || $normalized === '#') {
                continue;
            }

            // Phase 1: Exact match (do not map two different columns onto the same DB attribute)
            $matched = false;
            foreach ($dictionary as $attribute => $synonyms) {
                if (in_array($attribute, $map, true)) {
                    continue;
                }

                foreach ($synonyms as $synonym) {
                    if ($normalized === $this->cleanHeaderString($synonym)) {
                        $map[$colIndex] = $attribute;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if ($matched) {
                continue;
            }

            // Phase 2: Safe substring fallback (only if attribute not already mapped)
            foreach ($dictionary as $attribute => $synonyms) {
                if (in_array($attribute, $map, true)) {
                    continue;
                }

                foreach ($synonyms as $synonym) {
                    $cleanSynonym = $this->cleanHeaderString($synonym);
                    if ($cleanSynonym === '' || mb_strlen($cleanSynonym) < 3) {
                        continue;
                    }
                    if (str_contains($normalized, $cleanSynonym)) {
                        $map[$colIndex] = $attribute;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    protected function cleanHeaderString(string $header): string
    {
        $str = preg_replace('/[\r\n\t_]/u', ' ', $header) ?? '';
        $str = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $str) ?? '';
        $str = preg_replace('/[إأآا]/u', 'ا', $str) ?? '';
        $str = preg_replace('/ة/u', 'ه', $str) ?? '';
        $str = preg_replace('/[ىي]/u', 'ي', $str) ?? '';
        $str = preg_replace('/[^\p{L}\p{N} ]/u', '', $str) ?? '';

        return strtolower(trim(preg_replace('/\s+/', ' ', $str) ?? ''));
    }

    protected function parseDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '—' || $trimmed === '-') {
            return null;
        }

        if (is_numeric($trimmed) && (int) $trimmed > 20000 && (int) $trimmed < 65000) {
            try {
                return Carbon::create(1899, 12, 30)?->addDays((int) $trimmed)->format('Y-m-d');
            } catch (\Throwable) {
                // fallback
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'Y.m.d', 'd.m.Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $trimmed);
                if ($d instanceof DateTimeInterface) {
                    return $d->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            if (preg_match('/\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/', $trimmed) || preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/', $trimmed)) {
                return Carbon::parse($trimmed)->format('Y-m-d');
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    protected function parseDecimal(string $value): ?float
    {
        $clean = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $value)) ?? '';
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    protected function parseInt(string $value): ?int
    {
        $clean = preg_replace('/[^\d\-]/', '', $value) ?? '';
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (int) $clean;
    }

    /**
     * @return list<list<string>>
     */
    protected function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', ['Windows-1256', 'ISO-8859-6', 'ASCII']);
            if ($converted !== false) {
                $content = $converted;
            }
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $content = str_replace(["\r\n", "\r"], "\n", trim($content));
        if ($content === '') {
            return [];
        }

        $firstNewlinePos = strpos($content, "\n");
        $firstLine = $firstNewlinePos === false ? $content : substr($content, 0, $firstNewlinePos);
        $delimiter = ',';
        $maxCount = 0;
        foreach ([',', ';', "\t", '|'] as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $delim;
            }
        }

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return [];
        }

        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($parsed = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if ($parsed === [null] || empty($parsed)) {
                continue;
            }
            $cleaned = array_map(static fn (mixed $v): string => trim((string) $v), $parsed);
            if (implode('', $cleaned) === '') {
                continue;
            }
            $rows[] = $cleaned;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, list<string>>  $dictionary
     * @return list<list<string>>
     */
    protected function parseXls(string $path, array $dictionary): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        if (str_starts_with($content, "PK\x03\x04")) {
            return $this->parseXlsx($path, $dictionary);
        }

        if (str_contains($content, '<table') || str_contains($content, '<html') || str_contains($content, '<tr')) {
            return $this->parseHtmlTable($content);
        }

        return $this->parseCsv($path);
    }

    /**
     * @return list<list<string>>
     */
    protected function parseHtmlTable(string $html): array
    {
        $rows = [];
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $trMatches)) {
            foreach ($trMatches[1] as $trContent) {
                if (preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/si', $trContent, $tdMatches)) {
                    $row = [];
                    foreach ($tdMatches[1] as $cellHtml) {
                        $text = strip_tags($cellHtml);
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $row[] = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
                    }
                    if (! empty($row) && implode('', $row) !== '') {
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Native XLSX parser that ignores hidden sheets and selects the best-matching visible worksheet.
     *
     * @param  array<string, list<string>>  $dictionary
     * @return list<list<string>>
     */
    protected function parseXlsx(string $path, array $dictionary): array
    {
        $zip = new SimpleZipReader;
        if (! $zip->open($path)) {
            return [];
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml instanceof SimpleXMLElement && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $sheetTargets = [];
        if ($relsXml !== false) {
            $rels = @simplexml_load_string($relsXml);
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    $sheetTargets[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        $sheets = [];
        $activeTabIndex = null;
        if ($wbXml !== false) {
            $wb = @simplexml_load_string($wbXml);
            if ($wb && isset($wb->bookViews->workbookView['activeTab'])) {
                $activeTabIndex = (int) $wb->bookViews->workbookView['activeTab'];
            }
            if ($wb && isset($wb->sheets->sheet)) {
                $sheetIndex = 0;
                foreach ($wb->sheets->sheet as $s) {
                    $state = strtolower((string) ($s['state'] ?? 'visible'));
                    $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = ltrim($sheetTargets[$rId] ?? '', '/');
                    if ($target !== '' && ! in_array($state, ['hidden', 'veryhidden'], true)) {
                        $sheets[] = [
                            'isActive' => $activeTabIndex !== null && $sheetIndex === $activeTabIndex,
                            'target' => str_starts_with($target, 'xl/') ? $target : 'xl/'.$target,
                        ];
                    }
                    $sheetIndex++;
                }
            }
        }

        if (empty($sheets)) {
            foreach ($zip->entryNames() as $name) {
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheets[] = ['isActive' => false, 'target' => $name];
                }
            }
        }

        $bestRows = [];
        $bestScore = -1;

        foreach ($sheets as $sheetInfo) {
            $content = $zip->getFromName($sheetInfo['target']);
            if ($content === false) {
                continue;
            }
            $rows = $this->extractRowsFromSheetXml($content, $sharedStrings);
            if (empty($rows)) {
                continue;
            }

            $maxMatches = 0;
            for ($i = 0; $i < min(10, count($rows)); $i++) {
                $matches = count($this->buildHeaderMap($rows[$i], $dictionary));
                if ($matches > $maxMatches) {
                    $maxMatches = $matches;
                }
            }

            $score = ($maxMatches * 100) + ($sheetInfo['isActive'] ? 50 : 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRows = $rows;
            }
        }

        return $bestRows;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string>>
     */
    protected function extractRowsFromSheetXml(string $sheetXml, array $sharedStrings): array
    {
        $xml = @simplexml_load_string($sheetXml);
        if (! ($xml instanceof SimpleXMLElement) || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $lastCol = -1;

            foreach ($row->c as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $colIndex = $cellRef !== '' ? $this->columnRefToIndex($cellRef) : ($lastCol + 1);

                while ($lastCol + 1 < $colIndex) {
                    $rowData[] = '';
                    $lastCol++;
                }

                $type = (string) ($cell['t'] ?? '');
                $value = '';

                if ($type === 's') {
                    $idx = (int) ($cell->v ?? -1);
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    if (isset($cell->is->t)) {
                        $value = (string) $cell->is->t;
                    } elseif (isset($cell->is->r)) {
                        $rich = '';
                        foreach ($cell->is->r as $r) {
                            $rich .= (string) ($r->t ?? '');
                        }
                        $value = $rich;
                    }
                } elseif ($type === 'b') {
                    $value = ((string) ($cell->v ?? '')) === '1' ? 'نعم' : 'لا';
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }

                $rowData[] = trim($value);
                $lastCol = $colIndex;
            }

            if (! empty($rowData) && implode('', $rowData) !== '') {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    protected function columnRefToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', $cellRef) ?? '';
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }
}
