<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class StudentPhotoService
{
    /**
     * Stocke la photo d'un élève après redimensionnement.
     *
     * En cas d'échec du traitement par Intervention (ex. extension non supportée),
     * le fichier original est conservé sous son extension d'origine.
     */
    public function store(UploadedFile $photo, string $matricule): string
    {
        // SEC-03 : les photos d'élèves (données personnelles de mineurs) sont
        // stockées sur le disque privé `local` — jamais `public`, qui les
        // rendrait accessibles par URL directe sans aucune authentification
        // ni vérification de permission. Elles sont servies exclusivement via
        // StudentController::photo(), qui applique UserPolicy::voirDetailEleve().
        $filename = $matricule.'_'.time().'_'.Str::random(16).'.jpg';
        $path = 'photos/eleves/'.$filename;

        try {
            if (extension_loaded('imagick')) {
                Image::configure(['driver' => 'imagick']);
            }

            $image = Image::make($photo)->fit(150, 150)->encode('jpg', 90);
            Storage::disk('local')->put($path, $image);
        } catch (\Throwable $e) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $filename = $matricule.'_'.time().'_'.Str::random(16).'.'.$ext;
            $path = 'photos/eleves/'.$filename;
            Storage::disk('local')->putFileAs('photos/eleves', $photo, $filename);
        }

        return $path;
    }
}
