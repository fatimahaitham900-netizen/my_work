<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesModuleSpreadsheetImport;
use App\Http\Requests\CarRegulatoryDocuments\FilterCarRegulatoryDocumentsRequest;
use App\Http\Requests\CarRegulatoryDocuments\StoreCarRegulatoryDocumentRequest;
use App\Http\Requests\CarRegulatoryDocuments\UpdateCarRegulatoryDocumentRequest;
use App\Models\CarRegulatoryDocument;
use App\Services\CarRegulatoryDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarRegulatoryDocumentController extends Controller
{
    use HandlesModuleSpreadsheetImport;

    public function __construct(
        private readonly CarRegulatoryDocumentService $service,
    ) {}

    protected function importModuleKey(): string
    {
        return 'car-regulatory-documents';
    }

    protected function importModelClass(): string
    {
        return CarRegulatoryDocument::class;
    }

    protected function importContext(Request $request): array
    {
        $docType = trim((string) $request->input('document_type', ''));

        return $docType !== '' ? ['document_type' => $docType] : [];
    }

    protected function importRedirectParams(Request $request): array
    {
        $docType = trim((string) $request->input('document_type', ''));

        return $docType !== '' ? ['document_type' => $docType] : [];
    }

    public function index(FilterCarRegulatoryDocumentsRequest $request): View|JsonResponse
    {
        $filters = $request->filters();
        $activeDocumentType = trim((string) ($filters['document_type'] ?? ''));
        $records = $this->service->paginate($filters);
        $kpis = $this->service->kpis($filters);

        if ($request->header('X-Partial') === 'true' || $request->expectsJson()) {
            return response()->json([
                'kpis' => $kpis,
                'table_html' => view('modules.car-regulatory-documents._table', [
                    'records' => $records,
                    'activeDocumentType' => $activeDocumentType,
                ])->render(),
                'export_url' => route('car-regulatory-documents.export', array_filter($filters, static fn (mixed $v): bool => $v !== null && $v !== '')),
                'total' => $records->total(),
            ]);
        }

        return view('modules.car-regulatory-documents.index', [
            'records' => $records,
            'kpis' => $kpis,
            'filters' => $filters,
            'activeDocumentType' => $activeDocumentType,
            'documentTabs' => $this->service->documentTabs(),
            'options' => $this->service->filterOptions(),
        ]);
    }

    public function show(Request $request, CarRegulatoryDocument $carRegulatoryDocument): JsonResponse
    {
        Gate::authorize('view', $carRegulatoryDocument);

        return response()->json(
            $this->service->detailPayload(
                $carRegulatoryDocument,
                (bool) $request->user()?->canViewAuditLogs(),
            ),
        );
    }

    public function store(StoreCarRegulatoryDocumentRequest $request): JsonResponse|RedirectResponse
    {
        $document = $this->service->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تمت الإضافة بنجاح',
                'data' => $document,
            ], 201);
        }

        return redirect()
            ->route('car-regulatory-documents.index', ['document_type' => $document->document_type])
            ->with('status', 'تمت الإضافة بنجاح');
    }

    public function update(UpdateCarRegulatoryDocumentRequest $request, CarRegulatoryDocument $carRegulatoryDocument): JsonResponse|RedirectResponse
    {
        $document = $this->service->update($carRegulatoryDocument, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم التحديث بنجاح',
                'data' => $document,
            ]);
        }

        return redirect()
            ->route('car-regulatory-documents.index', ['document_type' => $document->document_type])
            ->with('status', 'تم التحديث بنجاح');
    }

    public function destroy(Request $request, CarRegulatoryDocument $carRegulatoryDocument): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $carRegulatoryDocument);

        $docType = $carRegulatoryDocument->document_type;
        $this->service->delete($carRegulatoryDocument);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم الحذف بنجاح',
            ]);
        }

        return redirect()
            ->route('car-regulatory-documents.index', ['document_type' => $docType])
            ->with('status', 'تم الحذف بنجاح');
    }

    public function export(FilterCarRegulatoryDocumentsRequest $request): StreamedResponse
    {
        return $this->service->exportCsv($request->filters());
    }
}
