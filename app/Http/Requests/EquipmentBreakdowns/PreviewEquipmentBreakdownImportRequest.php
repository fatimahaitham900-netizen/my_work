<?php

declare(strict_types=1);

namespace App\Http\Requests\EquipmentBreakdowns;

use Illuminate\Foundation\Http\FormRequest;

class PreviewEquipmentBreakdownImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageDeviations();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // max 10MB
                'extensions:csv,txt,xlsx,xls',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'يرجى اختيار ملف التحديث اليومي (CSV أو Excel).',
            'file.file' => 'الملف المرفوع غير صالح.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'file.extensions' => 'يجب أن يكون الملف بصيغة CSV أو Excel (xlsx, xls, csv).',
        ];
    }
}
