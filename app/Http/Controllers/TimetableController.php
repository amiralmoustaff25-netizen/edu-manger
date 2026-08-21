<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\TimetableEntry;
use App\Services\TimetableGridService;
use App\Support\TimetableGrid;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TimetableController extends Controller
{
    public function __construct(private readonly TimetableGridService $grid)
    {
    }

    /**
     * Liste des classes primaire de l'année active, point d'entrée pour un admin/surveillant
     * qui doit choisir quelle classe éditer (un professeur titulaire, lui, a un lien direct
     * depuis "Mes Classes" — voir teachers/classes/index.blade.php).
     */
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['super-admin', 'admin', 'surveillant']), 403);

        $schoolYear = SchoolYear::getActive();
        $classrooms = $schoolYear
            ? Classroom::where('school_year_id', $schoolYear->id)->where('cycle', 'primaire')->with('teacher')->orderBy('name')->get()
            : collect();

        return view('timetable.index', compact('classrooms', 'schoolYear'));
    }

    public function edit(Classroom $classroom)
    {
        $this->authorizeManage($classroom);

        $entries = $this->grid->grid($classroom);

        return view('timetable.edit', [
            'classroom' => $classroom,
            'entries' => $entries,
            'days' => TimetableGrid::DAYS,
            'slots' => TimetableGrid::SLOTS,
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorizeManage($classroom);

        $schoolYear = $classroom->schoolYear ?? SchoolYear::getActive();
        abort_unless($schoolYear, 422, "Aucune année scolaire associée à cette classe.");

        $content = $request->input('content', []);

        DB::transaction(function () use ($classroom, $schoolYear, $content) {
            foreach (TimetableGrid::DAYS as $day) {
                foreach (TimetableGrid::SLOTS as $slot) {
                    $value = trim((string) ($content[$day][$slot] ?? ''));

                    TimetableEntry::updateOrCreate(
                        ['classroom_id' => $classroom->id, 'school_year_id' => $schoolYear->id, 'day' => $day, 'slot' => $slot],
                        ['content' => $value !== '' ? $value : null, 'updated_by' => auth()->id()]
                    );
                }
            }
        });

        return back()->with('success', 'Emploi du temps enregistré.');
    }

    /**
     * Modèle Excel vierge (ou pré-rempli avec les cases déjà saisies) à télécharger, remplir
     * hors-ligne puis réimporter via import() — mêmes 10 lignes/6 colonnes dans le même ordre
     * que TimetableGrid, sans quoi import() rejette le fichier.
     */
    public function template(Classroom $classroom)
    {
        $this->authorizeManage($classroom);

        $entries = $this->grid->grid($classroom);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Emploi du temps');

        $sheet->setCellValue('A1', 'Horaire');
        foreach (TimetableGrid::DAYS as $col => $day) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 2).'1', $day);
        }

        foreach (TimetableGrid::SLOTS as $row => $slot) {
            $sheet->setCellValue('A'.($row + 2), $slot);
            foreach (TimetableGrid::DAYS as $col => $day) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 2).($row + 2), $entries[$day][$slot] ?? '');
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'emploi_du_temps_'.\Illuminate\Support\Str::slug($classroom->name).'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request, Classroom $classroom)
    {
        $this->authorizeManage($classroom);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $schoolYear = $classroom->schoolYear ?? SchoolYear::getActive();
        abort_unless($schoolYear, 422, "Aucune année scolaire associée à cette classe.");

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        // Forme attendue : ligne 1 = en-têtes (ignorées, la position fait foi), colonne A =
        // libellé du créneau (ignoré aussi), colonnes B à G = Lundi à Samedi, une ligne par
        // créneau de TimetableGrid::SLOTS dans le même ordre que le modèle téléchargeable.
        DB::transaction(function () use ($classroom, $schoolYear, $sheet) {
            foreach (TimetableGrid::SLOTS as $rowIndex => $slot) {
                $row = $rowIndex + 2;
                foreach (TimetableGrid::DAYS as $colIndex => $day) {
                    $col = $colIndex + 2;
                    $value = trim((string) ($sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getValue() ?? ''));

                    TimetableEntry::updateOrCreate(
                        ['classroom_id' => $classroom->id, 'school_year_id' => $schoolYear->id, 'day' => $day, 'slot' => $slot],
                        ['content' => $value !== '' ? $value : null, 'updated_by' => auth()->id()]
                    );
                }
            }
        });

        return back()->with('success', 'Emploi du temps importé depuis le fichier Excel.');
    }

    public function print(Classroom $classroom)
    {
        $this->authorizeView($classroom);

        $entries = $this->grid->grid($classroom);

        $pdf = Pdf::loadView('timetable.pdf', [
            'classroom' => $classroom,
            'entries' => $entries,
            'days' => TimetableGrid::DAYS,
            'slots' => TimetableGrid::SLOTS,
        ]);

        return $pdf->download('emploi_du_temps_'.\Illuminate\Support\Str::slug($classroom->name).'.pdf');
    }

    /**
     * Droit de modifier l'emploi du temps : admin/super-admin, surveillant (n'importe quelle
     * classe), ou le professeur titulaire de CETTE classe (Classroom::teacher_id) — voir
     * l'échange avec l'utilisateur du 2026-08-21 ("c'est au professeur principal et au
     * surveillant de remplir l'emploi du temps").
     */
    private function authorizeManage(Classroom $classroom): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super-admin', 'admin', 'surveillant'])) {
            return;
        }

        if ($user->hasRole('professeur') && $classroom->teacher_id === $user->id) {
            return;
        }

        abort(403, "Seul le professeur principal de cette classe, un surveillant ou un administrateur peut modifier l'emploi du temps.");
    }

    /**
     * Droit de consulter/imprimer (plus large que modifier) : les mêmes acteurs que
     * authorizeManage(), plus tout professeur ayant une affectation pédagogique active dans
     * cette classe (cohérent avec BulletinController::authorizeCanViewRib()-like checks).
     */
    private function authorizeView(Classroom $classroom): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super-admin', 'admin', 'surveillant'])) {
            return;
        }

        if ($user->hasRole('professeur')) {
            $teacher = $user->teacher;
            $isTitulaire = $classroom->teacher_id === $user->id;
            $hasAssignment = $teacher && $classroom->pedagogicalAssignments()->where('teacher_id', $teacher->id)->where('is_active', true)->exists();

            if ($isTitulaire || $hasAssignment) {
                return;
            }
        }

        abort(403);
    }
}
