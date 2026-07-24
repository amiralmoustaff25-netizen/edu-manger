<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_recurring',
        'is_optional',
    ];

    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
            'is_optional' => 'boolean',
        ];
    }

    public function classroomFees(): HasMany
    {
        return $this->hasMany(ClassroomFee::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
