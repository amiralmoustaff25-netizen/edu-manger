<?php

namespace App\Models;

use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'classroom_id', 'registration_fee_paid',
        'monthly_fee', 'options', 'registration_date', 'academic_year',
        'school_year_id', 'matricule', 'status',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'options' => 'array',
    ];

    protected static function newFactory()
    {
        return RegistrationFactory::new();
    }

    /**
     * Relation avec l'élève (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec la classe
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    /**
     * Relation avec les paiements
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    // Scope pour les inscriptions actives
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope pour les inscriptions par année scolaire
    public function scopeForYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    // Scope pour les inscriptions par classe
    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    // Accessor pour le statut formaté
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'pending' => 'En attente',
            'completed' => 'Complété',
            'cancelled' => 'Annulé',
            default => ucfirst($this->status),
        };
    }
}
