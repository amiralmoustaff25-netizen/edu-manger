<?php

namespace App\Http\Controllers;

use App\Models\StudentDocument;
use App\Models\User;
use App\Support\StudentDocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

        $file = $request->file('file');
        $path = $file->store('student-documents/'.$student->id, 'local');

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
}
