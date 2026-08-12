<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $filename = $matricule.'_'.time().'.jpg';
        $path = 'photos/eleves/'.$filename;

        try {
            if (extension_loaded('imagick')) {
                Image::configure(['driver' => 'imagick']);
            }

            $image = Image::make($photo)->fit(150, 150)->encode('jpg', 90);
            Storage::disk('public')->put($path, $image);
        } catch (\Throwable $e) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $filename = $matricule.'_'.time().'.'.$ext;
            $path = 'photos/eleves/'.$filename;
            Storage::disk('public')->putFileAs('photos/eleves', $photo, $filename);
        }

        return $path;
    }
}
