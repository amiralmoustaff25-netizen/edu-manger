<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'registration_id',
        'payment_id',
        'amount',
        'reason',
        'status',
        'used_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    /**
     * Relation avec l'inscription
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Relation avec le paiement
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Marquer le crédit comme utilisé
     */
    public function markAsUsed()
    {
        $this->status = 'used';
        $this->used_at = now();
        $this->save();
    }

    /**
     * Obtenir le crédit total disponible pour une inscription
     */
    public static function getAvailableCredit($registrationId)
    {
        return self::where('registration_id', $registrationId)
            ->where('status', 'available')
            ->sum('amount');
    }
}
