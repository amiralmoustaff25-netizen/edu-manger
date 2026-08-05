<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'classroom_id', 'matiere_id', 'valeur', 'type_evaluation', 'periode', 'appreciation', 'validated_at', 'validated_by'];

    protected $casts = ['validated_at' => 'datetime'];

    protected static function newFactory()
    {
        return NoteFactory::new();
    }

    // Une note appartient à un élève (User)
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Une note appartient à une classe
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // Une note appartient à une matière
    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    // Utilisateur (administration) ayant validé la note
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Une note validée est verrouillée : seule une réouverture privilégiée permet de la modifier.
    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function validate(int $validatorId): void
    {
        $this->update(['validated_at' => now(), 'validated_by' => $validatorId]);
    }

    public function reopen(): void
    {
        $this->update(['validated_at' => null, 'validated_by' => null]);
    }

    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    public function scopeNotValidated($query)
    {
        return $query->whereNull('validated_at');
    }

    // Scope pour filtrer par période
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('periode', $period);
    }

    // Scope pour filtrer par type d'évaluation
    public function scopeForType($query, string $type)
    {
        return $query->where('type_evaluation', $type);
    }

    // Scope pour les notes supérieures à un seuil
    public function scopeAbove($query, float $threshold)
    {
        return $query->where('valeur', '>=', $threshold);
    }

    // Accessor pour obtenir la note sur 20 formatée
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->valeur, 2) . '/20';
    }
}
