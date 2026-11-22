<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SparePartTransfers\FilterSparePartTransfersRequest;
use App\Http\Requests\SparePartTransfers\StoreSparePartTransferRequest;
use App\Http\Requests\SparePartTransfers\UpdateSparePartTransferRequest;
use App\Models\SparePartTransfer;
use App\Services\SparePartTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SparePartTransferController extends Controller
{
    public function __construct(
        private readonly SparePartTransferService $service,
    ) {}

    public function index(FilterSparePartTransfersRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.spare-part-transfers._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('spare-part-transfers.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.spare-part-transfers.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, SparePartTransfer $sparePartTransfer): JsonResponse
    {
        Gate::authorize('view', $sparePartTransfer);

        return response()->json(
            $this->service->detailPayload(
                $sparePartTransfer,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreSparePartTransferRequest $request): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $transfer,
            ], 201);
        }

        return redirect()
            ->route('spare-part-transfers.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateSparePartTransferRequest $request, SparePartTransfer $sparePartTransfer): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->update($sparePartTransfer, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $transfer,
            ]);
        }

        return redirect()
            ->route('spare-part-transfers.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, SparePartTransfer $sparePartTransfer): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $sparePartTransfer);

        $this->service->delete($sparePartTransfer);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('spare-part-transfers.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterSparePartTransfersRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
