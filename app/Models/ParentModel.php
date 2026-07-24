<?php

namespace App\Models;

use Database\Factories\ParentModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'parents';

    protected static $factory = ParentModelFactory::class;

    protected $fillable = [
        'matricule_parent',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'profession',
        'statut',
        'user_id',
    ];

    protected $casts = [
        'est_responsable_financier' => 'boolean',
        'est_contact_urgence' => 'boolean',
    ];

    /**
     * Relation avec le compte utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les élèves (Users avec role='eleve')
     * Un parent peut avoir plusieurs enfants
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_user', 'parent_id', 'user_id')
            ->withPivot('lien_parente', 'est_responsable_financier', 'est_contact_urgence')
            ->withTimestamps()
            ->role('eleve');
    }

    /**
     * Scope pour filtrer les parents actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope pour filtrer les parents en attente d'activation
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente_activation');
    }

    /**
     * Scope pour filtrer les parents archivés
     */
    public function scopeArchives($query)
    {
        return $query->where('statut', 'archive');
    }

    /**
     * Scope pour rechercher par nom, prénom, email, téléphone ou matricule
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
                ->orWhere('prenom', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telephone', 'like', "%{$search}%")
                ->orWhere('matricule_parent', 'like', "%{$search}%");
        });
    }

    /**
     * Générer un matricule parent unique
     */
    public static function generateMatricule(): string
    {
        $prefix = 'PAR';
        $year = date('y');
        $sequence = 1;

        do {
            $matricule = $prefix.'-'.$year.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::withTrashed()->where('matricule_parent', $matricule)->exists());

        return $matricule;
    }

    /**
     * Obtenir le responsable financier principal d'un élève
     */
    public function scopeResponsableFinancier($query)
    {
        return $query->whereHas('students', function ($q) {
            $q->where('est_responsable_financier', true);
        });
    }

    protected static function newFactory()
    {
        return ParentModelFactory::new();
    }

    /**
     * Obtenir les contacts d'urgence
     */
    public function scopeContactUrgence($query)
    {
        return $query->whereHas('students', function ($q) {
            $q->where('est_contact_urgence', true);
        });
    }
}
