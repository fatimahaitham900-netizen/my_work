<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OwnershipTransferStatus;
use App\Http\Requests\OwnershipTransfers\FilterOwnershipTransfersRequest;
use App\Http\Requests\OwnershipTransfers\StoreOwnershipTransferRequest;
use App\Http\Requests\OwnershipTransfers\UpdateOwnershipTransferRequest;
use App\Models\OwnershipTransfer;
use App\Services\OwnershipTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnershipTransferController extends Controller
{
    public function __construct(
        private readonly OwnershipTransferService $service,
    ) {}

    public function index(FilterOwnershipTransfersRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis();

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.ownership-transfers._table', [
                    'records' => $records,
                ])->render(),
                'export_url' => route('ownership-transfers.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.ownership-transfers.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'statuses' => OwnershipTransferStatus::cases(),
            'branches' => $this->service->branches(),
        ]);
    }

    public function show(Request $request, OwnershipTransfer $ownershipTransfer): JsonResponse
    {
        Gate::authorize('view', $ownershipTransfer);

        return response()->json(
            $this->service->detailPayload(
                $ownershipTransfer,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreOwnershipTransferRequest $request): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $transfer,
            ], 201);
        }

        return redirect()
            ->route('ownership-transfers.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateOwnershipTransferRequest $request, OwnershipTransfer $ownershipTransfer): JsonResponse|RedirectResponse
    {
        $transfer = $this->service->update($ownershipTransfer, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $transfer,
            ]);
        }

        return redirect()
            ->route('ownership-transfers.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, OwnershipTransfer $ownershipTransfer): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $ownershipTransfer);

        $this->service->delete($ownershipTransfer);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('ownership-transfers.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterOwnershipTransfersRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
