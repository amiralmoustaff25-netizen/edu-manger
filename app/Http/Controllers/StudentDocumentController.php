<?php

namespace App\Http\Controllers;

use App\Models\StudentDocument;
use App\Models\User;
use App\Support\StudentDocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentController extends Controller
{
    public function store(Request $request, User $student): RedirectResponse
    {
        $this->authorize('gerer-documents-eleve');
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::in(StudentDocumentType::values())],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->ensureMimeTypeIsAllowed($request->file('file'), ['application/pdf', 'image/jpeg', 'image/png']);

        $file = $request->file('file');
        $directory = 'student-documents/'.$student->id;

        // Les images sont ré-encodées afin de supprimer les métadonnées EXIF
        // (GPS, appareil, etc.) avant stockage. Les PDF sont conservés tels quels.
        if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'], true)) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg';
            $path = $directory.'/'.$filename;

            if (extension_loaded('imagick')) {
                Image::configure(['driver' => 'imagick']);
            }

            $encoded = Image::make($file)
                ->orientate()
                ->encode('jpg', 95);

            Storage::disk('local')->put($path, $encoded);
        } else {
            $path = $file->store($directory, 'local');
        }

        StudentDocument::create([
            'user_id' => $student->id,
            'type' => $validated['type'],
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Document ajouté avec succès.');
    }

    public function download(User $student, StudentDocument $document): StreamedResponse
    {
        $this->authorize('voir-detail-eleve', $student);

        abort_unless(
            auth()->user()->isTeacherAssignedToStudent($student),
            403,
            "Vous n'êtes pas autorisé à consulter le dossier de cet élève."
        );

        abort_unless($document->user_id === $student->id, 404);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function destroy(User $student, StudentDocument $document): RedirectResponse
    {
        $this->authorize('gerer-documents-eleve');
        abort_unless($document->user_id === $student->id, 404);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

    private function ensureMimeTypeIsAllowed($file, array $allowedTypes): void
    {
        $detectedMime = $file->getMimeType();

        if (! in_array($detectedMime, $allowedTypes, true)) {
            abort(422, 'Le type de fichier détecté ('.$detectedMime.') n\'est pas autorisé.');
        }
    }
}
