<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Notifications\Notification;

class StudentAbsent extends Notification
{
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
