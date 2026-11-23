<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BreakdownCategory;
use App\Enums\BreakdownSeverity;
use App\Enums\DriverStoppageStatus;
use App\Enums\RepairStatus;
use App\Http\Requests\EquipmentBreakdowns\FilterEquipmentBreakdownsRequest;
use App\Http\Requests\EquipmentBreakdowns\PreviewEquipmentBreakdownImportRequest;
use App\Http\Requests\EquipmentBreakdowns\StoreEquipmentBreakdownRequest;
use App\Http\Requests\EquipmentBreakdowns\UpdateEquipmentBreakdownRequest;
use App\Models\EquipmentBreakdown;
use App\Services\EquipmentBreakdownDailyImportService;
use App\Services\EquipmentBreakdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentBreakdownController extends Controller
{
    public function __construct(
        private readonly EquipmentBreakdownService $service,
        private readonly EquipmentBreakdownDailyImportService $dailyImportService,
    ) {}

    public function index(FilterEquipmentBreakdownsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.equipment-breakdowns._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('equipment-breakdowns.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.equipment-breakdowns.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'workshops' => $this->service->workshops(),
            'workshopStatuses' => $this->service->workshopStatuses(),
            'equipmentNames' => $this->service->equipmentNames(),
            'brands' => $this->service->brands(),
            'brandMatrix' => $this->service->buildWorkshopMatrix('brand'),
            'statusMatrix' => $this->service->buildWorkshopMatrix('workshop_status'),
            'equipmentMatrix' => $this->service->buildWorkshopMatrix('equipment_name'),
            'categories' => BreakdownCategory::cases(),
            'severities' => BreakdownSeverity::cases(),
            'repairStatuses' => RepairStatus::cases(),
            'importPreview' => session('equipment_breakdown_import_preview'),
        ]);
    }

    public function show(Request $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse
    {
        Gate::authorize('view', $equipmentBreakdown);

        return response()->json(
            $this->service->detailPayload(
                $equipmentBreakdown,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreEquipmentBreakdownRequest $request): JsonResponse|RedirectResponse
    {
        $breakdown = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $breakdown,
            ], 201);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateEquipmentBreakdownRequest $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $resolveLinkedStoppages = (bool) ($validated['resolve_linked_stoppages'] ?? false);
        $linkedStatus = DriverStoppageStatus::from((string) ($validated['linked_stoppage_status'] ?? DriverStoppageStatus::Resolved->value));

        $result = $this->service->update(
            $equipmentBreakdown,
            Arr::except($validated, ['resolve_linked_stoppages', 'linked_stoppage_status']),
            $resolveLinkedStoppages,
            $linkedStatus,
        );

        $message = $result['resolved_stoppages_count'] > 0
            ? sprintf('تم التحديث بنجاح وتم تحديث حالة %d سائق مرتبط بالعطل', $result['resolved_stoppages_count'])
            : 'تم التحديث بنجاح';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $result['breakdown'],
                'resolved_stoppages_count' => $result['resolved_stoppages_count'],
            ]);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', $message);
    }

    public function destroy(Request $request, EquipmentBreakdown $equipmentBreakdown): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $equipmentBreakdown);

        $this->service->delete($equipmentBreakdown);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterEquipmentBreakdownsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }

    public function directImport(PreviewEquipmentBreakdownImportRequest $request): RedirectResponse
    {
        Gate::authorize('create', EquipmentBreakdown::class);

        try {
            $result = $this->dailyImportService->importDirect($request->file('file'));

            if ($result['total'] === 0) {
                return redirect()
                    ->route('equipment-breakdowns.index')
                    ->with('error', 'لم يتم العثور على أي صفوف صالحة داخل الملف المرفوع. يرجى التأكد من أن الملف يحتوي على أعمدة البيانات أو تحميل النموذج المعتمد.');
            }

            session()->forget('equipment_breakdown_import_preview');

            $message = $result['deleted'] > 0
                ? sprintf('تم إسقاط وتحديث البيانات على الجدول مباشرة بنجاح: تم إدراج وتحديث %d معدة، وحذف %d معدة قديمة غير موجودة بالملف.', $result['created'] + $result['updated'], $result['deleted'])
                : sprintf('تم إسقاط وتحديث البيانات على الجدول مباشرة بنجاح: تم إدراج وتحديث %d معدة.', $result['created'] + $result['updated']);

            return redirect()
                ->route('equipment-breakdowns.index')
                ->with('status', $message);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('equipment-breakdowns.index')
                ->with('error', 'تعذر إتمام الإسقاط والتحديث بسبب خطأ أثناء معالجة الملف: '.$e->getMessage());
        }
    }

    public function previewImport(PreviewEquipmentBreakdownImportRequest $request): RedirectResponse
    {
        Gate::authorize('create', EquipmentBreakdown::class);

        $preview = $this->dailyImportService->parseFile($request->file('file'));

        if ($preview['total_count'] === 0) {
            return redirect()
                ->route('equipment-breakdowns.index')
                ->with('error', 'لم يتم العثور على أي صفوف أو أكواد معدات صالحة داخل الملف المرفوع.');
        }

        session()->put('equipment_breakdown_import_preview', $preview);

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تمت قراءة ومعالجة ملف التحديث اليومي بنجاح. يرجى مراجعة المعاينة والمقارنة أدناه قبل التطبيق النهائي.');
    }

    public function applyImport(Request $request): RedirectResponse
    {
        Gate::authorize('create', EquipmentBreakdown::class);

        /** @var array<string, mixed>|null $preview */
        $preview = session('equipment_breakdown_import_preview');

        if (empty($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('equipment-breakdowns.index')
                ->with('error', 'لا توجد بيانات معاينة معلقة للاعتماد. يرجى رفع ملف التحديث أولاً.');
        }

        $result = $this->dailyImportService->applyImport($preview);
        session()->forget('equipment_breakdown_import_preview');

        $message = $result['deleted'] > 0
            ? sprintf('تم تطبيق التحديثات اليومية بنجاح: تم تحديث البيانات الجديدة بالكامل (%d معدة)، وحذف كافة البيانات القديمة غير الموجودة بالملف (%d معدة).', $result['created'] + $result['updated'], $result['deleted'])
            : sprintf('تم تطبيق التحديثات اليومية بنجاح: تم إضافة وتحديث %d معدة في قاعدة البيانات.', $result['created'] + $result['updated']);

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', $message);
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        Gate::authorize('create', EquipmentBreakdown::class);

        session()->forget('equipment_breakdown_import_preview');

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', 'تم إلغاء المعاينة ومسح بيانات الملف المؤقتة دون إجراء أي تعديل على قاعدة البيانات.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('viewAny', EquipmentBreakdown::class);

        return $this->dailyImportService->generateTemplateStream();
    }

    public function purgeAll(Request $request): RedirectResponse
    {
        Gate::authorize('create', EquipmentBreakdown::class);

        $deletedCount = $this->dailyImportService->purgeAll();
        session()->forget('equipment_breakdown_import_preview');

        $message = $deletedCount > 0
            ? sprintf('تم حذف جميع بيانات المعدات المتعطلة بالورش (%d سجل) بنجاح. يمكنك الآن رفع الملف الجديد وإسقاطه مباشرة.', $deletedCount)
            : 'الجدول فارغ بالفعل ولا توجد بيانات لحذفها.';

        return redirect()
            ->route('equipment-breakdowns.index')
            ->with('status', $message);
    }
}
