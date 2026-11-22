<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceDeviations\FilterMaintenanceDeviationsRequest;
use App\Http\Requests\MaintenanceDeviations\StoreMaintenanceDeviationRequest;
use App\Http\Requests\MaintenanceDeviations\UpdateMaintenanceDeviationRequest;
use App\Models\MaintenanceDeviation;
use App\Services\MaintenanceDeviationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceDeviationController extends Controller
{
    public function __construct(
        private readonly MaintenanceDeviationService $service,
    ) {}

    public function index(FilterMaintenanceDeviationsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $summary = $this->service->summaryAverages();
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.maintenance-deviations._table', [
                    'records' => $records,
                    'summary' => $summary,
                ])->render(),
                'export_url' => route('maintenance-deviations.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.maintenance-deviations.index', [
            'records' => $records,
            'summary' => $summary,
            'kpis' => $kpis,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, MaintenanceDeviation $maintenanceDeviation): JsonResponse
    {
        Gate::authorize('view', $maintenanceDeviation);

        return response()->json(
            $this->service->detailPayload(
                $maintenanceDeviation,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreMaintenanceDeviationRequest $request): JsonResponse|RedirectResponse
    {
        $record = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $record,
            ], 201);
        }

        return redirect()
            ->route('maintenance-deviations.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateMaintenanceDeviationRequest $request, MaintenanceDeviation $maintenanceDeviation): JsonResponse|RedirectResponse
    {
        $record = $this->service->update($maintenanceDeviation, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $record,
            ]);
        }

        return redirect()
            ->route('maintenance-deviations.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, MaintenanceDeviation $maintenanceDeviation): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $maintenanceDeviation);

        $this->service->delete($maintenanceDeviation);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('maintenance-deviations.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterMaintenanceDeviationsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
