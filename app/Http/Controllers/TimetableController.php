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
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TimetableController extends Controller
{
    public function __construct(private readonly TimetableGridService $grid) {}

    /**
     * Liste des classes (tous cycles) de l'année active, point d'entrée pour un admin/
     * surveillant qui doit choisir quelle classe éditer (un professeur titulaire, lui, a un
     * lien direct depuis "Mes Classes" — voir teachers/classes/index.blade.php). Groupées
     * par cycle pour la lisibilité, plus long que la version primaire-only initiale.
     */
    public function index()
    {
        $this->authorize('viewAnyTimetable', Classroom::class);

        $schoolYear = SchoolYear::getActive();
        $classrooms = $schoolYear
            ? Classroom::where('school_year_id', $schoolYear->id)->with('teacher')->orderBy('cycle')->orderBy('name')->get()->groupBy('cycle')
            : collect();

        return view('timetable.index', compact('classrooms', 'schoolYear'));
    }

    public function edit(Classroom $classroom)
    {
        $this->authorize('manageTimetable', $classroom);

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
        $this->authorize('manageTimetable', $classroom);

        $schoolYear = $classroom->schoolYear ?? SchoolYear::getActive();
        abort_unless($schoolYear, 422, 'Aucune année scolaire associée à cette classe.');

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
        $this->authorize('manageTimetable', $classroom);

        $entries = $this->grid->grid($classroom);

        $spreadsheet = new Spreadsheet;
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
        $filename = 'emploi_du_temps_'.Str::slug($classroom->name).'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request, Classroom $classroom)
    {
        $this->authorize('manageTimetable', $classroom);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        $schoolYear = $classroom->schoolYear ?? SchoolYear::getActive();
        abort_unless($schoolYear, 422, 'Aucune année scolaire associée à cette classe.');

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
        $this->authorize('viewTimetable', $classroom);

        $entries = $this->grid->grid($classroom);

        $pdf = Pdf::loadView('timetable.pdf', [
            'classroom' => $classroom,
            'entries' => $entries,
            'days' => TimetableGrid::DAYS,
            'slots' => TimetableGrid::SLOTS,
        ]);

        return $pdf->download('emploi_du_temps_'.Str::slug($classroom->name).'.pdf');
    }
}
