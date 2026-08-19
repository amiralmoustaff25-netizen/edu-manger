<?php

namespace App\Models;

use Database\Factories\MatiereFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'coefficient', 'bareme'];
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function pedagogicalAssignments()
    {
        return $this->hasMany(PedagogicalAssignment::class);
    }

    public function configurations()
    {
        return $this->hasMany(SubjectConfiguration::class);
    }
}
