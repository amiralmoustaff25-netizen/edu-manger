<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolConfiguration extends Model
{
    protected $fillable = [
        'school_name',
        'school_logo',
        'address',
        'phone',
        'email',
        'website',
        'bank_name',
        'account_number',
        'iban',
        'swift',
        'overpayment_mode',
        'sequential_payment_rule',
        'allow_future_payment',
        'is_configured',
        'configured_at',
    ];

    protected $casts = [
        'is_configured' => 'boolean',
        'sequential_payment_rule' => 'boolean',
        'allow_future_payment' => 'boolean',
        'configured_at' => 'datetime',
    ];

    /**
     * Obtenir la configuration actuelle de l'école (singleton)
     */
    public static function current()
    {
        return self::firstOrCreate([], [
            'overpayment_mode' => 'change',
            'sequential_payment_rule' => true,
            'allow_future_payment' => false,
            'is_configured' => false,
        ]);
    }

    /**
     * Marquer la configuration comme terminée
     */
    public function markAsConfigured()
    {
        $this->is_configured = true;
        $this->configured_at = now();
        $this->save();
    }

    /**
     * Vérifier si l'école est configurée
     */
    public static function isConfigured()
    {
        return self::where('is_configured', true)->exists();
    }
}
