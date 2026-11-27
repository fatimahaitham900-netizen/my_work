<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Drivers\FilterDriversRequest;
use App\Http\Requests\Drivers\StoreDriverRequest;
use App\Http\Requests\Drivers\UpdateDriverRequest;
use App\Models\Driver;
use App\Services\DriverService;
use App\Services\ModuleSpreadsheetImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $service,
        private readonly ModuleSpreadsheetImportService $importService,
    ) {}

    public function index(FilterDriversRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeView = match ($filters['view'] ?? 'table') {
            'summary' => 'summary',
            'equipment_type_summary' => 'equipment_type_summary',
            'workshop_staff' => 'workshop_staff',
            default => 'table',
        };
        $staffCategory = $activeView === 'workshop_staff'
            ? Driver::CATEGORY_WORKSHOP_STAFF
            : Driver::CATEGORY_DRIVER;

        $records = $this->service->paginate($filters);
        $filteredSalaryTotal = $this->service->filteredSalaryTotal($filters);
        $kpis = $this->service->kpis();
        $summary = $this->service->summary($activeView === 'workshop_staff' ? [] : $filters);

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            $partialView = match ($activeView) {
                'summary' => 'modules.drivers._summary',
                'equipment_type_summary' => 'modules.drivers._equipment_type_summary',
                'workshop_staff' => 'modules.drivers._workshop_staff_table',
                default => 'modules.drivers._table',
            };

            return response()->json([
                'kpis' => $kpis,
                'table_html' => view($partialView, [
                    'records' => $records,
                    'filteredSalaryTotal' => $filteredSalaryTotal,
                    'summary' => $summary,
                ])->render(),
                'export_url' => route('drivers.export', array_filter($filters)),
                'total' => $records->total(),
            ]);
        }

        return view('modules.drivers.index', [
            'records' => $records,
            'filteredSalaryTotal' => $filteredSalaryTotal,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeView' => $activeView,
            'summary' => $summary,
            'options' => $this->service->filterOptions($staffCategory),
        ]);
    }

    public function show(Request $request, Driver $driver): JsonResponse
    {
        Gate::authorize('view', $driver);

        return response()->json(
            $this->service->detailPayload(
                $driver,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['staff_category'] = $data['staff_category'] ?? Driver::CATEGORY_DRIVER;
        $data['salary'] = $data['salary'] ?? 0;

        $record = $this->service->create($data);

        return response()->json([
            'message' => $data['staff_category'] === Driver::CATEGORY_WORKSHOP_STAFF
                ? 'تم إضافة بيانات كادر الورشة بنجاح'
                : 'تم إضافة بيانات السائق بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ], 201);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $data = $request->validated();
        $data['salary'] = $data['salary'] ?? 0;

        $record = $this->service->update($driver, $data);

        return response()->json([
            'message' => 'تم تحديث بيانات السائق بنجاح',
            'data' => $this->service->detailPayload($record, false),
        ]);
    }

    public function destroy(Driver $driver): JsonResponse
    {
        Gate::authorize('delete', $driver);

        $this->service->delete($driver);

        return response()->json([
            'message' => 'تم حذف السائق بنجاح',
        ]);
    }

    public function export(FilterDriversRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('manage-deviations');

        return $this->importService->generateTemplateStream('drivers');
    }

    public function directImport(Request $request): RedirectResponse
    {
        Gate::authorize('manage-deviations');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
            'staff_category' => ['nullable', 'string', 'in:driver,workshop_staff'],
        ], [
            'file.required' => 'يرجى اختيار ملف CSV أو Excel لرفع البيانات.',
            'file.mimes' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: CSV, XLSX, XLS.',
        ]);

        $staffCategory = (string) ($validated['staff_category'] ?? Driver::CATEGORY_DRIVER);
        $redirectParams = $staffCategory === Driver::CATEGORY_WORKSHOP_STAFF
            ? ['view' => 'workshop_staff']
            : [];

        try {
            $result = $this->importService->importDirect('drivers', $validated['file'], [
                'staff_category' => $staffCategory,
            ]);
        } catch (Throwable $e) {
            return redirect()
                ->route('drivers.index', $redirectParams)
                ->withErrors(['file' => 'تعذر قراءة الملف المرفق: '.$e->getMessage()]);
        }

        if ($result['imported'] === 0) {
            return redirect()
                ->route('drivers.index', $redirectParams)
                ->withErrors(['file' => 'لم يتم العثور على صفوف صالحة للاستيراد داخل الملف.']);
        }

        $message = $staffCategory === Driver::CATEGORY_WORKSHOP_STAFF
            ? "تم استيراد {$result['imported']} موظف في كادر الورش بنجاح (وحذف {$result['deleted']} سجل قديم)."
            : "تم استيراد {$result['imported']} سائق بنجاح وربط الفروع والمواقع تلقائياً من توزيعة المعدات (وحذف {$result['deleted']} سجل قديم).";

        return redirect()
            ->route('drivers.index', $redirectParams)
            ->with('status', $message);
    }

    public function purgeAll(Request $request): RedirectResponse
    {
        Gate::authorize('delete-deviations');

        $staffCategory = (string) $request->input('staff_category', Driver::CATEGORY_DRIVER);
        if (! in_array($staffCategory, [Driver::CATEGORY_DRIVER, Driver::CATEGORY_WORKSHOP_STAFF], true)) {
            $staffCategory = Driver::CATEGORY_DRIVER;
        }

        $deleted = $this->importService->purgeAll('drivers', [
            'staff_category' => $staffCategory,
        ]);

        $redirectParams = $staffCategory === Driver::CATEGORY_WORKSHOP_STAFF
            ? ['view' => 'workshop_staff']
            : [];

        $label = $staffCategory === Driver::CATEGORY_WORKSHOP_STAFF ? 'كادر الورش' : 'السائقين';

        return redirect()
            ->route('drivers.index', $redirectParams)
            ->with('status', "تم تفريغ جدول {$label} وحذف جميع البيانات ({$deleted} سجل) بنجاح.");
    }
}
