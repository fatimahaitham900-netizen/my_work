<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransportViolations\FilterTransportViolationsRequest;
use App\Http\Requests\TransportViolations\StoreTransportViolationRequest;
use App\Http\Requests\TransportViolations\UpdateTransportViolationRequest;
use App\Models\TransportViolation;
use App\Services\TransportViolationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportViolationController extends Controller
{
    public function __construct(
        private readonly TransportViolationService $service,
    ) {}

    public function index(FilterTransportViolationsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $filteredAmountTotal = $this->service->filteredAmountTotal($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.transport-violations._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                ])->render(),
                'export_url' => route('transport-violations.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.transport-violations.index', [
            'records' => $records,
            'filteredAmountTotal' => $filteredAmountTotal,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, TransportViolation $transportViolation): JsonResponse
    {
        Gate::authorize('view', $transportViolation);

        return response()->json(
            $this->service->detailPayload(
                $transportViolation,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreTransportViolationRequest $request): JsonResponse|RedirectResponse
    {
        $violation = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $violation,
            ], 201);
        }

        return redirect()
            ->route('transport-violations.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateTransportViolationRequest $request, TransportViolation $transportViolation): JsonResponse|RedirectResponse
    {
        $violation = $this->service->update($transportViolation, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $violation,
            ]);
        }

        return redirect()
            ->route('transport-violations.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, TransportViolation $transportViolation): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $transportViolation);

        $this->service->delete($transportViolation);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('transport-violations.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterTransportViolationsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
