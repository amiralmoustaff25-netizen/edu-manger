<?php

namespace App\Models;

use App\Support\StudentDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return StudentDocumentType::label($this->type);
    }
}
