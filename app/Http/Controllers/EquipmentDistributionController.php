<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentDistributions\FilterEquipmentDistributionsRequest;
use App\Http\Requests\EquipmentDistributions\StoreEquipmentDistributionRequest;
use App\Http\Requests\EquipmentDistributions\UpdateEquipmentDistributionRequest;
use App\Models\EquipmentDistribution;
use App\Services\EquipmentDistributionService;
use App\Services\ModuleSpreadsheetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EquipmentDistributionController extends Controller
{
    public function __construct(
        private readonly EquipmentDistributionService $service,
        private readonly ModuleSpreadsheetImportService $importService,
    ) {}

    public function index(FilterEquipmentDistributionsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeView = match ($filters['view'] ?? 'table') {
            'summary' => 'summary',
            'site_summary' => 'site_summary',
            'equipment_drivers' => 'equipment_drivers',
            default => 'table',
        };
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();
        $summary = $this->service->summaryByType($filters);
        $siteSummary = $this->service->summaryBySite($filters);
        $equipmentDriversSummary = $this->service->summaryEquipmentAndDrivers($filters);

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            $partialView = match ($activeView) {
                'summary' => 'modules.equipment-distributions._summary',
                'site_summary' => 'modules.equipment-distributions._site_summary',
                'equipment_drivers' => 'modules.equipment-distributions._equipment_drivers',
                default => 'modules.equipment-distributions._table',
            };

            return response()->json([
                'kpis' => $kpis,
                'table_html' => view($partialView, [
                    'records' => $records,
                    'summary' => $summary,
                    'siteSummary' => $siteSummary,
                    'equipmentDriversSummary' => $equipmentDriversSummary,
                ])->render(),
                'export_url' => route('equipment-distributions.export', array_filter($filters)),
                'total' => $records->total(),
            ]);
        }

        return view('modules.equipment-distributions.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeView' => $activeView,
            'summary' => $summary,
            'siteSummary' => $siteSummary,
            'equipmentDriversSummary' => $equipmentDriversSummary,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, EquipmentDistribution $equipmentDistribution): JsonResponse
    {
        Gate::authorize('view', $equipmentDistribution);

        return response()->json(
            $this->service->detailPayload(
                $equipmentDistribution,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreEquipmentDistributionRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json([
            'message' => 'تم إضافة سجل توزيعة المعدة بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ], 201);
    }

    public function update(UpdateEquipmentDistributionRequest $request, EquipmentDistribution $equipmentDistribution): JsonResponse
    {
        $record = $this->service->update($equipmentDistribution, $request->validated());

        return response()->json([
            'message' => 'تم تحديث سجل توزيعة المعدة بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ]);
    }

    public function destroy(EquipmentDistribution $equipmentDistribution): JsonResponse
    {
        Gate::authorize('delete', $equipmentDistribution);

        $this->service->delete($equipmentDistribution);

        return response()->json([
            'message' => 'تم حذف السجل بنجاح',
        ]);
    }

    public function export(FilterEquipmentDistributionsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('manage-deviations');

        return $this->importService->generateTemplateStream('equipment-distributions');
    }

    public function directImport(Request $request): RedirectResponse
    {
        Gate::authorize('manage-deviations');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ], [
            'file.required' => 'يرجى اختيار ملف CSV أو Excel لرفع البيانات.',
            'file.mimes' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: CSV, XLSX, XLS.',
        ]);

        try {
            $result = $this->importService->importDirect('equipment-distributions', $validated['file']);
        } catch (Throwable $e) {
            return redirect()
                ->route('equipment-distributions.index')
                ->withErrors(['file' => 'تعذر قراءة الملف المرفق: '.$e->getMessage()]);
        }

        if ($result['imported'] === 0) {
            return redirect()
                ->route('equipment-distributions.index')
                ->withErrors(['file' => 'لم يتم العثور على صفوف صالحة للاستيراد داخل الملف.']);
        }

        return redirect()
            ->route('equipment-distributions.index')
            ->with('status', "تم استيراد وإسقاط {$result['imported']} سجل في توزيعة المعدات بنجاح (وحذف {$result['deleted']} سجل قديم).");
    }

    public function purgeAll(): RedirectResponse
    {
        Gate::authorize('delete-deviations');

        $deleted = $this->importService->purgeAll('equipment-distributions');

        return redirect()
            ->route('equipment-distributions.index')
            ->with('status', "تم تفريغ جدول توزيعة المعدات وحذف جميع البيانات ({$deleted} سجل) بنجاح.");
    }
}
