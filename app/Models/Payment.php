<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Payment extends Model
{
    protected $fillable = [
        'registration_id',
        'amount',
        'status',          // 'complet' ou 'partiel'
        'remaining_balance', // Le reste à payer si partiel
        'month',           // Le mois concerné (ex: 'Octobre')
        'validated_by',    // ID du manager qui a validé si partiel
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            // Compte le nombre de paiements existants pour générer le prochain numéro
            $year = date('Y');
            $count = Payment::whereYear('created_at', $year)->count() + 1;
            $payment->receipt_number = 'REC-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Relation avec l'inscription.
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Relation avec l'utilisateur qui a validé le paiement.
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
