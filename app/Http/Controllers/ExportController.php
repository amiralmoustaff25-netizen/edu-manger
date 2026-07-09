<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExportController extends Controller
{
    /**
     * Générer un PDF Hello World
     */
    public function pdfHelloWorld()
    {
        $data = [
            'title' => 'Hello World PDF',
            'date' => now()->format('d/m/Y H:i:s'),
            'message' => 'Ceci est un exemple de génération PDF avec DomPDF dans Laravel 12!'
        ];

        $pdf = Pdf::loadView('exports.hello-world-pdf', $data);
        
        return $pdf->download('hello-world.pdf');
    }

    /**
     * Générer un Excel Hello World
     */
    public function excelHelloWorld()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Titre
        $sheet->setCellValue('A1', 'Hello World Excel');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A1:D1');

        // Date
        $sheet->setCellValue('A3', 'Date de génération:');
        $sheet->setCellValue('B3', now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A3')->getFont()->setBold(true);

        // Message
        $sheet->setCellValue('A5', 'Message:');
        $sheet->setCellValue('B5', 'Ceci est un exemple de génération Excel avec PhpSpreadsheet dans Laravel 12!');
        $sheet->getStyle('A5')->getFont()->setBold(true);

        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        
        $filename = 'hello-world.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Aperçu du PDF dans le navigateur
     */
    public function pdfPreview()
    {
        $data = [
            'title' => 'Hello World PDF',
            'date' => now()->format('d/m/Y H:i:s'),
            'message' => 'Ceci est un exemple de génération PDF avec DomPDF dans Laravel 12!'
        ];

        $pdf = Pdf::loadView('exports.hello-world-pdf', $data);
        
        return $pdf->stream('hello-world.pdf');
    }
}
