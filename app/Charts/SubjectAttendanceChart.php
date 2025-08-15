<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class SubjectAttendanceChart extends Chart
{
    public function __construct(array $labels, array $studentsPresent)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($studentsPresent)) {
            $labels = ['No Data'];
            $studentsPresent = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Students Present', 'bar', $studentsPresent)
            ->color('teal')
            ->backgroundColor('teal');
        
        // Add chart options for better handling of empty data
        $this->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Subjects/Classes'
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Students Present'
                    ]
                ]
            ]
        ]);
    }
}
