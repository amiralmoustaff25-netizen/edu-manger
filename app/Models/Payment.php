<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return PaymentFactory::new();
    }

    /**
     * Les attributs mass-assignables.
     */
    protected $fillable = [
        'registration_id',
        'amount',
        'status',               // 'complet' ou 'partiel'
        'remaining_balance',  // Le reste à payer si partiel
        'month',              // Le mois concerné (ex: 'Octobre')
        'payment_date',       // Date du paiement
        'payment_method',     // Méthode de paiement (espèces, virement, etc.)
        'payment_type',       // Type de paiement (inscription, mensualité, etc.)
        'comment',            // Commentaire éventuel
        'validated_by',       // ID du manager qui a validé si partiel
        'validated_at',       // Date de validation
        'receipt_number',     // Numéro de reçu
        'fee_breakdown',      // Détail des frais couverts
        'cancelled_at',       // Date d'annulation (audit, jamais de suppression physique d'un paiement validé)
        'cancelled_by',       // ID de l'utilisateur ayant annulé le paiement
        'cancellation_reason', // Motif obligatoire de l'annulation
    ];

    /**
     * Les attributs à caster.
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'validated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'fee_breakdown' => 'array',
        ];
    }

    /**
     * Génération du numéro de reçu avec verrouillage pour éviter les doublons.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $year = now()->year;

            // Transaction avec verrouillage pour éviter la race condition
            $nextNumber = DB::transaction(function () use ($year) {
                // Verrouillage pessimiste : bloque les autres requêtes pendant le comptage
                $count = Payment::whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->count();

                return $count + 1;
            });

            $payment->receipt_number = 'REC-'.$year.'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Relation avec l'inscription.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Relation avec l'utilisateur qui a validé le paiement.
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation avec l'utilisateur qui a annulé le paiement.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'payment_invoice')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }

    /**
     * Vérifie si le paiement est complet.
     */
    public function isComplete(): bool
    {
        return $this->status === 'complet';
    }

    /**
     * Vérifie si le paiement est partiel.
     */
    public function isPartial(): bool
    {
        return $this->status === 'partiel';
    }

    /**
     * Scope pour les paiements complets.
     */
    public function scopeComplete($query)
    {
        return $query->where('status', 'complet');
    }

    /**
     * Scope pour les paiements partiels.
     */
    public function scopePartial($query)
    {
        return $query->where('status', 'partiel');
    }

    /**
     * Scope pour les paiements d'un mois donné.
     */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope pour les paiements d'une année scolaire.
     */
    public function scopeForYear($query, int $year)
    {
        // BDD-12 : la date métier du paiement est `payment_date`, pas la date
        // d'enregistrement en base (`created_at`), qui peut diverger si un
        // paiement est saisi après coup.
        return $query->whereYear('payment_date', $year);
    }

    /**
     * Scope pour les paiements en attente de validation.
     */
    public function scopePendingValidation($query)
    {
        return $query->where('status', 'partiel')->whereNull('validated_at');
    }

    /**
     * Scope pour les paiements validés.
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    /**
     * Valider un paiement partiel.
     */
    public function validatePayment($validatorId): void
    {
        $this->status = 'complet';
        $this->validated_by = $validatorId;
        $this->validated_at = now();
        $this->remaining_balance = 0;
        $this->save();
    }

    /**
     * Marquer un paiement comme nécessitant une validation.
     */
    public function markForValidation(): void
    {
        $this->status = 'partiel';
        $this->save();
    }

    /**
     * Un paiement est considéré "validé" (donc protégé contre la suppression physique)
     * dès qu'il est complet, ou qu'un partiel a été explicitement validé.
     */
    public function isValidated(): bool
    {
        return $this->status === 'complet' || ! is_null($this->validated_at);
    }

    /**
     * Vérifie si le paiement a été annulé.
     */
    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    /**
     * Annule le paiement avec traçabilité complète (auteur + motif + date).
     * Règle métier : un paiement validé n'est jamais supprimé, uniquement annulé.
     */
    public function cancel(int $userId, string $reason): void
    {
        $this->cancelled_at = now();
        $this->cancelled_by = $userId;
        $this->cancellation_reason = $reason;
        $this->save();
    }

    /**
     * Scope excluant les paiements annulés (pour les totaux financiers).
     */
    public function scopeNotCancelled($query)
    {
        return $query->whereNull('cancelled_at');
    }
}
