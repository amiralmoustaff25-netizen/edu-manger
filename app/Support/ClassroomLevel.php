<?php

namespace App\Support;

use App\Models\Classroom;
use Illuminate\Support\Str;

/**
 * Ordre pédagogique standard des niveaux (CI → Terminale), dérivé du menu déroulant
 * "Niveau" déjà utilisé par le formulaire de classe (voir ClassroomController). Permet de
 * déterminer "la classe suivante" pour la promotion automatique (sous-étape E), sans
 * dupliquer cette liste ni demander à l'admin de saisir un numéro d'ordre à la main.
 */
class ClassroomLevel
{
    public const ORDER = [
        'CI' => 1, 'CP' => 2, 'CE1' => 3, 'CE2' => 4, 'CM1' => 5, 'CM2' => 6,
        '6ème' => 7, '5ème' => 8, '4ème' => 9, '3ème' => 10,
        'Seconde' => 11, 'Première' => 12, 'Terminale' => 13,
    ];

    public static function ordre(string $level): ?int
    {
        return self::ORDER[$level] ?? null;
    }

    /** Niveau suivant dans le même cycle, ou null en fin de cycle (terminale d'un cycle). */
    public static function nextLevel(string $level): ?string
    {
        $ordre = self::ordre($level);

        if ($ordre === null) {
            return null;
        }

        return array_search($ordre + 1, self::ORDER, true) ?: null;
    }

    /**
     * Complète rétroactivement la colonne `ordre` des classes déjà existantes (créées
     * avant l'ajout de cette colonne), en devinant le niveau depuis le nom de la classe
     * ("CM2 A" -> "CM2"). Les classes dont le nom ne correspond à aucun niveau connu
     * gardent `ordre = null` : pas de fausse certitude, la promotion automatique devra
     * alors demander un choix manuel pour ces classes-là.
     */
    public static function backfillOrdre(): void
    {
        Classroom::whereNull('ordre')->get()->each(function (Classroom $classroom) {
            foreach (array_keys(self::ORDER) as $level) {
                if ($classroom->name === $level || Str::startsWith($classroom->name, $level.' ')) {
                    $classroom->update(['ordre' => self::ORDER[$level]]);

                    return;
                }
            }
        });
    }
}
