<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_chapter_id',
        'date_traitement',
        'completed_by',
        'remarque',
    ];

    protected $casts = [
        'date_traitement' => 'date',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ProgramChapter::class, 'program_chapter_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
