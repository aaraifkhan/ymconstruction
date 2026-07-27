<?php

namespace App\Models;

use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'document_id',
    'version',
    'disk',
    'path',
    'original_file_name',
    'mime_type',
    'extension',
    'size',
    'checksum',
    'uploaded_by_id',
    'notes',
])]
class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    protected $attributes = [
        'disk' => 'local',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Document versions are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('Document versions are immutable and cannot be deleted.');
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size' => 'integer',
        ];
    }
}
