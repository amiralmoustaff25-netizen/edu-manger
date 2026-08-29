<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenchée pour chaque élève marqué absent dans une soumission de présences (voir
 * AttendanceController::store()) — un professeur pointant toute une classe peut
 * déclencher plusieurs envois dans la même requête, mise en file pour ne pas ralentir
 * la réponse "présences enregistrées".
 */
class StudentAbsent extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected Attendance $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $student = $this->attendance->student;
        $classroom = $this->attendance->classroom;

        return [
            'attendance_id' => $this->attendance->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'date' => $this->attendance->date?->format('Y-m-d'),
            'classroom' => $classroom->name ?? 'Non assignée',
            'title' => 'Absence enregistrée',
            'type' => 'important',
            'priority' => 'high',
            'category' => 'attendance',
            'content' => "Votre enfant {$student->name} a été marqué(e) absent(e) le ".($this->attendance->date?->format('d/m/Y') ?? 'DATE').'.',
        ];
    }
}
