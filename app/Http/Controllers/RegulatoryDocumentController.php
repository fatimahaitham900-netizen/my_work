<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ComplianceStatus;
use App\Enums\RegulatoryEntityType;
use App\Http\Requests\RegulatoryDocuments\FilterRegulatoryDocumentsRequest;
use App\Http\Requests\RegulatoryDocuments\StoreRegulatoryDocumentRequest;
use App\Http\Requests\RegulatoryDocuments\UpdateRegulatoryDocumentRequest;
use App\Models\RegulatoryDocument;
use App\Services\RegulatoryDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegulatoryDocumentController extends Controller
{
    public function __construct(
        private readonly RegulatoryDocumentService $service,
    ) {}

    public function index(FilterRegulatoryDocumentsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeDocumentType = trim((string) ($filters['document_type'] ?? ''));
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis($filters);

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.regulatory-documents._table', [
                    'records' => $records,
                    'activeDocumentType' => $activeDocumentType,
                ])->render(),
                'export_url' => route('regulatory-documents.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.regulatory-documents.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeDocumentType' => $activeDocumentType,
            'documentTabs' => $this->service->documentTabs(),
            'options' => $this->service->filterOptions(),
            'entityTypes' => RegulatoryEntityType::cases(),
            'complianceStatuses' => ComplianceStatus::cases(),
        ]);
    }

    public function show(Request $request, RegulatoryDocument $regulatoryDocument): JsonResponse
    {
        Gate::authorize('view', $regulatoryDocument);

        return response()->json(
            $this->service->detailPayload(
                $regulatoryDocument,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreRegulatoryDocumentRequest $request): JsonResponse|RedirectResponse
    {
        $document = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $document,
            ], 201);
        }

        return redirect()
            ->route('regulatory-documents.index')
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateRegulatoryDocumentRequest $request, RegulatoryDocument $regulatoryDocument): JsonResponse|RedirectResponse
    {
        $document = $this->service->update($regulatoryDocument, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $document,
            ]);
        }

        return redirect()
            ->route('regulatory-documents.index')
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, RegulatoryDocument $regulatoryDocument): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $regulatoryDocument);

        $this->service->delete($regulatoryDocument);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('regulatory-documents.index')
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterRegulatoryDocumentsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
