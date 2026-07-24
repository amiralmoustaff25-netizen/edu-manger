<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return null;
    }

    public function view(User $user, Note $note): bool
    {
        // Un élève ne peut voir que ses propres notes
        if ($user->id === $note->user_id) {
            return true;
        }

        // Un professeur peut voir une note s'il enseigne dans la classe de la note
        if ($user->hasRole('professeur')) {
            return $user->classrooms()->where('classrooms.id', $note->classroom_id)->exists();
        }

        return false;
    }

    public function update(User $user, Note $note): bool
    {
        // Un professeur peut modifier une note s'il enseigne dans la classe de la note
        if ($user->hasRole('professeur')) {
            return $user->classrooms()->where('classrooms.id', $note->classroom_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('professeur');
    }
}
