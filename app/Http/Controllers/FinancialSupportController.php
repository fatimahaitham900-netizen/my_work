<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
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
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly FinancialSupportService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'financial-support';
    }

    protected function importModelClass(): string
    {
        return FinancialSupportTransfer::class;
    }

    protected function importContext(Request $request): array
    {
        $month = (string) $request->input('month', '');
        $year = (int) $request->input('year', now()->year);

        return is_numeric($month) && (int) $month >= 1 && (int) $month <= 12
            ? ['support_month' => (int) $month, 'support_year' => $year]
            : [];
    }

    protected function importRedirectParams(Request $request): array
    {
        $month = trim((string) $request->input('month', ''));

        return $month !== '' ? ['month' => $month] : [];
    }

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

    public function updateMonthCustody(Request $request, FinancialSupportMonth $financialSupportMonth): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $financialSupportMonth);

        $rawCustody = str_replace([',', ' '], '', (string) $request->input('custody_amount', '0'));
        $request->merge([
            'custody_amount' => $rawCustody === '' ? 0 : $rawCustody,
        ]);

        $validated = $request->validate([
            'custody_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $updated = $this->service->updateMonthCustody($financialSupportMonth, (float) $validated['custody_amount']);
        $summary = $this->service->dashboardTableSummary();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تحديث مبلغ العهد بنجاح',
                'data' => [
                    'id' => $updated->id,
                    'month' => (int) $updated->month,
                    'repair_support_amount' => (float) $updated->repair_support_amount,
                    'custody_amount' => (float) $updated->custody_amount,
                    'total_amount' => (float) $updated->total_amount,
                ],
                'totals' => $summary['totals'],
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'تم تحديث مبلغ العهد بنجاح');
    }

    public function export(FilterFinancialSupportRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
