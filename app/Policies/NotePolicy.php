<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Le verrouillage des notes validées (update/reopen) ne doit jamais être contourné
        // ici, même pour un admin : seule la méthode dédiée ci-dessous décide.
        if (in_array($ability, ['update', 'reopen'], true)) {
            return null;
        }

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
        // Une note validée est verrouillée : elle doit être réouverte (reopen) avant toute modification.
        if ($note->isValidated()) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

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

    // Validation administrative d'un lot de notes (verrouillage)
    public function validateNotes(User $user): bool
    {
        return $user->can('valider-notes');
    }

    // Réouverture d'une note validée : action privilégiée (super-admin uniquement, via Gate::before global)
    public function reopen(User $user, Note $note): bool
    {
        return $user->can('rouvrir-notes-validees');
    }
}
