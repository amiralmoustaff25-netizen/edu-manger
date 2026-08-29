<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    /**
     * Seules valeurs réellement acceptées par la colonne status (ENUM MySQL, voir
     * database/migrations/2026_07_13_155028_create_invoices_table.php). Les vues et le
     * contrôleur utilisaient un vocabulaire différent ('pending', 'cancelled' — jamais
     * valides ici) : sélectionner l'un de ces statuts dans le formulaire d'édition
     * provoquait une violation de contrainte sur MySQL, jamais détectée en local
     * (SQLite n'impose pas l'ENUM). Centralisé ici pour que formulaire, filtre et
     * affichage ne puissent plus diverger de la colonne réelle.
     */
    public const STATUSES = ['draft', 'sent', 'paid', 'partial', 'overdue'];

    public const LABELS = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'paid' => 'Payée',
        'partial' => 'Partiellement payée',
        'overdue' => 'En retard',
    ];

    public const BADGE_COLORS = [
        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    ];

    protected $fillable = [
        'registration_id',
        'invoice_number',
        'total_amount',
        'remaining_balance',
        'due_date',
        'status',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'due_date' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_invoice')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }
}
