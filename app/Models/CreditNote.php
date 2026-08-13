<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    use HasFactory;

    // NB (BDD-06) : `remaining_amount` est une colonne générée en base
    // (storedAs('amount - used_amount')) — elle est calculée automatiquement
    // par le SGBD et ne doit jamais être écrite directement, sous peine
    // d'erreur SQL. Elle est donc volontairement absente de $fillable.
    protected $fillable = [
        'registration_id',
        'amount',
        'reason',
        'used_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'used_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
