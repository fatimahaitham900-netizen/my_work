<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\CarBreakdowns\FilterCarBreakdownsRequest;
use App\Http\Requests\CarBreakdowns\StoreCarBreakdownRequest;
use App\Http\Requests\CarBreakdowns\UpdateCarBreakdownRequest;
use App\Models\CarBreakdown;
use App\Services\CarBreakdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarBreakdownController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly CarBreakdownService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'car-breakdowns';
    }

    protected function importModelClass(): string
    {
        return CarBreakdown::class;
    }

    public function index(FilterCarBreakdownsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.car-breakdowns._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('car-breakdowns.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.car-breakdowns.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, CarBreakdown $carBreakdown): JsonResponse
    {
        Gate::authorize('view', $carBreakdown);

        return response()->json(
            $this->service->detailPayload(
                $carBreakdown,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreCarBreakdownRequest $request): JsonResponse|RedirectResponse
    {
        $carBreakdown = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $carBreakdown,
            ], 201);
        }

        return redirect()
            ->route('car-breakdowns.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateCarBreakdownRequest $request, CarBreakdown $carBreakdown): JsonResponse|RedirectResponse
    {
        $carBreakdown = $this->service->update($carBreakdown, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $carBreakdown,
            ]);
        }

        return redirect()
            ->route('car-breakdowns.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, CarBreakdown $carBreakdown): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $carBreakdown);

        $this->service->delete($carBreakdown);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('car-breakdowns.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterCarBreakdownsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
