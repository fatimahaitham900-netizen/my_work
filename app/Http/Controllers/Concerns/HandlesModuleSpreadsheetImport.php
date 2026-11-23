<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\ImportSpreadsheetRequest;
use App\Services\ModuleSpreadsheetImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesModuleSpreadsheetImport
{
    abstract protected function importModuleKey(): string;

    /**
     * @return class-string
     */
    abstract protected function importModelClass(): string;

    /**
     * Optional scoped context (e.g. document_type or support_month).
     *
     * @return array<string, mixed>
     */
    protected function importContext(Request $request): array
    {
        return [];
    }

    /**
     * Redirect route parameters when returning to module index.
     *
     * @return array<string, mixed>
     */
    protected function importRedirectParams(Request $request): array
    {
        return [];
    }

    public function directImport(ImportSpreadsheetRequest $request, ModuleSpreadsheetImportService $importService): RedirectResponse
    {
        Gate::authorize('create', $this->importModelClass());

        $module = $this->importModuleKey();
        $routeParams = $this->importRedirectParams($request);

        try {
            $result = $importService->importDirect($module, $request->file('file'), $this->importContext($request));

            if ($result['imported'] === 0) {
                return redirect()
                    ->route("{$module}.index", $routeParams)
                    ->with('error', 'لم يتم العثور على أي صفوف صالحة داخل الملف المرفوع. يرجى التأكد من تطابق رؤوس الأعمدة أو تحميل النموذج الجاهز.');
            }

            $message = $result['deleted'] > 0
                ? sprintf('تم إسقاط وتحديث البيانات بنجاح: تم إدراج %d سجل جديد وحذف %d سجل قديم.', $result['imported'], $result['deleted'])
                : sprintf('تم إسقاط البيانات على الجدول بنجاح: تم إدراج %d سجل.', $result['imported']);

            return redirect()
                ->route("{$module}.index", $routeParams)
                ->with('status', $message);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route("{$module}.index", $routeParams)
                ->with('error', 'تعذر إتمام الإسقاط بسبب خطأ أثناء معالجة الملف: '.$e->getMessage());
        }
    }

    public function downloadTemplate(ModuleSpreadsheetImportService $importService): StreamedResponse
    {
        Gate::authorize('viewAny', $this->importModelClass());

        return $importService->generateTemplateStream($this->importModuleKey());
    }

    public function purgeAll(Request $request, ModuleSpreadsheetImportService $importService): RedirectResponse
    {
        Gate::authorize('create', $this->importModelClass());

        $module = $this->importModuleKey();
        $deletedCount = $importService->purgeAll($module, $this->importContext($request));

        $message = $deletedCount > 0
            ? sprintf('تم حذف جميع البيانات (%d سجل) بنجاح. يمكنك الآن رفع الملف الجديد وإسقاطه مباشرة.', $deletedCount)
            : 'الجدول فارغ بالفعل ولا توجد بيانات لحذفها.';

        return redirect()
            ->route("{$module}.index", $this->importRedirectParams($request))
            ->with('status', $message);
    }
}
