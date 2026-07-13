<?php

namespace App\Policies;

use App\Models\ChapterCompletion;
use App\Models\User;

class ChapterCompletionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('voir-cahier-textes');
    }

    public function create(User $user): bool
    {
        return $user->can('saisir-cahier-textes');
    }

    public function update(User $user, ChapterCompletion $completion): bool
    {
        return $user->id === $completion->chapter->program->teacher_id || $user->hasRole(['super-admin', 'admin']);
    }
}
