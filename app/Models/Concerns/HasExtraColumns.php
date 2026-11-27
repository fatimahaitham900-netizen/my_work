<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasExtraColumns
{
    public function initializeHasExtraColumns(): void
    {
        $this->mergeCasts([
            'extra_columns' => 'array',
        ]);
    }

    /**
     * @return list<string>
     */
    public function getFillable(): array
    {
        $fillable = parent::getFillable();

        if (! in_array('extra_columns', $fillable, true)) {
            $fillable[] = 'extra_columns';
        }

        return $fillable;
    }
}
