<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class AttendanceTrendsChart extends Chart
{
    public function __construct(array $labels, array $present, array $absent)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($present) || empty($absent)) {
            $labels = ['No Data'];
            $present = [0];
            $absent = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Present', 'line', $present)
            ->color('green')
            ->backgroundColor('rgba(0,128,0,0.1)')
            ->fill(true);
        $this->dataset('Absent', 'line', $absent)
            ->color('red')
            ->backgroundColor('rgba(255,0,0,0.1)')
            ->fill(true);
        
        // Add chart options for better handling of empty data
        $this->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Students'
                    ]
                ]
            ]
        ]);
    }
}
