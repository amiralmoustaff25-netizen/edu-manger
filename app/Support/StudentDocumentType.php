<?php

namespace App\Support;

class StudentDocumentType
{
    public const LABELS = [
        'acte_naissance' => 'Acte de naissance',
        'certificat_medical' => 'Certificat médical',
        'piece_identite' => "Copie pièce d'identité",
        'certificat_scolarite_precedent' => 'Certificat de scolarité (année précédente)',
        'bulletin_precedent' => 'Bulletin (année précédente)',
        'autre' => 'Autre document',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? $type;
    }

    public static function values(): array
    {
        return array_keys(self::LABELS);
    }
}
