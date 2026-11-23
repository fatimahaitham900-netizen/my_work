<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UnassignedDriverStatus;
use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\UnassignedDrivers\FilterUnassignedDriversRequest;
use App\Http\Requests\UnassignedDrivers\StoreUnassignedDriverRequest;
use App\Http\Requests\UnassignedDrivers\UpdateUnassignedDriverRequest;
use App\Http\Requests\UnassignedDrivers\UpdateUnassignedDriverStatusRequest;
use App\Models\UnassignedDriver;
use App\Services\UnassignedDriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnassignedDriverController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly UnassignedDriverService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'unassigned-drivers';
    }

    protected function importModelClass(): string
    {
        return UnassignedDriver::class;
    }

    public function index(FilterUnassignedDriversRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.unassigned-drivers._table', [
                    'records' => $records,
                    'statuses' => UnassignedDriverStatus::cases(),
                ])->render(),
                'export_url' => route('unassigned-drivers.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.unassigned-drivers.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'statuses' => UnassignedDriverStatus::cases(),
            'locations' => $this->service->locations(),
        ]);
    }

    public function show(Request $request, UnassignedDriver $unassignedDriver): JsonResponse
    {
        Gate::authorize('view', $unassignedDriver);

        return response()->json(
            $this->service->detailPayload(
                $unassignedDriver,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreUnassignedDriverRequest $request): JsonResponse|RedirectResponse
    {
        $driver = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $driver,
            ], 201);
        }

        return redirect()
            ->route('unassigned-drivers.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateUnassignedDriverRequest $request, UnassignedDriver $unassignedDriver): JsonResponse|RedirectResponse
    {
        $driver = $this->service->update($unassignedDriver, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $driver,
            ]);
        }

        return redirect()
            ->route('unassigned-drivers.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function updateStatus(UpdateUnassignedDriverStatusRequest $request, UnassignedDriver $unassignedDriver): JsonResponse|RedirectResponse
    {
        $status = UnassignedDriverStatus::from($request->string('status')->value());
        $driver = $this->service->updateStatus($unassignedDriver, $status);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $driver,
            ]);
        }

        return redirect()
            ->route('unassigned-drivers.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, UnassignedDriver $unassignedDriver): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $unassignedDriver);

        $this->service->delete($unassignedDriver);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('unassigned-drivers.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterUnassignedDriversRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
