<?php

namespace App\Models;

use App\Enums\SalesDocumentType;
use Database\Factories\SalesSequenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'document_type', 'calendar_year', 'prefix', 'next_number', 'padding', 'is_active'])]
class SalesSequence extends Model
{
    /** @use HasFactory<SalesSequenceFactory> */
    use HasFactory;

    protected $attributes = ['next_number' => 1, 'padding' => 6, 'is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'document_type' => SalesDocumentType::class,
            'calendar_year' => 'integer',
            'next_number' => 'integer',
            'padding' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
