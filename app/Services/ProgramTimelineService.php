<?php

namespace App\Services;

class ProgramTimelineService
{
    public function getMonthlyTimeline(): array
    {
        $labels = [];
        $data = [];

        for ($i = 0; $i < 12; $i++) {
            $labels[] = now()->subMonths(11 - $i)->translatedFormat('M');
            $data[] = 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
