<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FinancialSupport\FilterFinancialSupportRequest;
use App\Http\Requests\FinancialSupport\StoreFinancialSupportTransferRequest;
use App\Http\Requests\FinancialSupport\UpdateFinancialSupportMonthRequest;
use App\Http\Requests\FinancialSupport\UpdateFinancialSupportTransferRequest;
use App\Models\FinancialSupportMonth;
use App\Models\FinancialSupportTransfer;
use App\Services\FinancialSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialSupportController extends Controller
{
    public function __construct(
        private readonly FinancialSupportService $service,
    ) {}

    public function index(FilterFinancialSupportRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeMonth = $this->service->resolveActiveMonth($filters);
        $filters['month'] = $activeMonth;

        $records = $this->service->paginate($filters);
        $filteredAmountTotal = $this->service->filteredAmountTotal($filters);
        $kpis = $this->service->kpis($filters);
        $monthTabs = $this->service->monthTabs();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.financial-support._table', [
                    'records' => $records,
                    'filteredAmountTotal' => $filteredAmountTotal,
                    'activeMonth' => $activeMonth,
                ])->render(),
                'export_url' => route('financial-support.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.financial-support.index', [
            'records' => $records,
            'filteredAmountTotal' => $filteredAmountTotal,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeMonth' => $activeMonth,
            'monthTabs' => $monthTabs,
            'summaryTable' => $this->service->dashboardTableSummary(),
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, FinancialSupportTransfer $financialSupportTransfer): JsonResponse
    {
        Gate::authorize('view', $financialSupportTransfer);

        return response()->json(
            $this->service->detailPayload(
                $financialSupportTransfer,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreFinancialSupportTransferRequest $request): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->createTransfer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $transfer,
            ], 201);
        }

        return redirect()
            ->route('financial-support.index', ['month' => $transfer->support_month])
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateFinancialSupportTransferRequest $request, FinancialSupportTransfer $financialSupportTransfer): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->updateTransfer($financialSupportTransfer, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $transfer,
            ]);
        }

        return redirect()
            ->route('financial-support.index', ['month' => $transfer->support_month])
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, FinancialSupportTransfer $financialSupportTransfer): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $financialSupportTransfer);

        $month = $financialSupportTransfer->support_month;
        $this->service->deleteTransfer($financialSupportTransfer);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('financial-support.index', ['month' => $month])
            ->with('status', 'تم الحذف بنجاح');
    }

    public function updateMonth(UpdateFinancialSupportMonthRequest $request, FinancialSupportMonth $financialSupportMonth): JsonResponse|RedirectResponse
    {
        $updated = $this->service->updateMonth($financialSupportMonth, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تحديث ملخص الشهر بنجاح',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->route('financial-support.index', ['month' => $updated->month])
            ->with('status', 'تم تحديث ملخص الشهر بنجاح');
    }

    public function export(FilterFinancialSupportRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
