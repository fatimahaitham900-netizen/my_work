<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DriverStoppageStatus;
use App\Http\Requests\BreakdownStoppages\FilterBreakdownDriverStoppagesRequest;
use App\Http\Requests\BreakdownStoppages\StoreBreakdownDriverStoppageRequest;
use App\Http\Requests\BreakdownStoppages\UpdateBreakdownDriverStoppageRequest;
use App\Models\BreakdownDriverStoppage;
use App\Services\BreakdownDriverStoppageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BreakdownDriverStoppageController extends Controller
{
    public function __construct(
        private readonly BreakdownDriverStoppageService $service,
    ) {}

    public function index(FilterBreakdownDriverStoppagesRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.breakdown-stoppages._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('breakdown-stoppages.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.breakdown-stoppages.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'statuses' => DriverStoppageStatus::cases(),
            'branches' => $this->service->branches(),
            'workshopStatuses' => $this->service->workshopStatuses(),
            'activeBreakdowns' => $this->service->activeBreakdowns(),
        ]);
    }

    public function show(Request $request, BreakdownDriverStoppage $breakdownStoppage): JsonResponse
    {
        Gate::authorize('view', $breakdownStoppage);

        return response()->json(
            $this->service->detailPayload(
                $breakdownStoppage,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreBreakdownDriverStoppageRequest $request): JsonResponse|RedirectResponse
    {
        $stoppage = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $stoppage,
            ], 201);
        }

        return redirect()
            ->route('breakdown-stoppages.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateBreakdownDriverStoppageRequest $request, BreakdownDriverStoppage $breakdownStoppage): JsonResponse|RedirectResponse
    {
        $stoppage = $this->service->update($breakdownStoppage, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $stoppage,
            ]);
        }

        return redirect()
            ->route('breakdown-stoppages.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, BreakdownDriverStoppage $breakdownStoppage): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $breakdownStoppage);

        $this->service->delete($breakdownStoppage);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('breakdown-stoppages.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterBreakdownDriverStoppagesRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
