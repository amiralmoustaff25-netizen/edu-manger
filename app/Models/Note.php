<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    // Autoriser le remplissage de ces champs
    protected $fillable = ['user_id', 'classroom_id', 'matiere_id', 'valeur', 'type_evaluation', 'periode', 'appreciation'];

    // Une note appartient à un élève (User)
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Une note appartient à une classe
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    // Une note appartient à une matière
    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }
}
