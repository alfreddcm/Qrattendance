<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class StudentForecastChart extends Chart
{
    public function __construct(array $labels, array $attendance)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($attendance)) {
            $labels = ['No Data'];
            $attendance = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Attendance', 'line', $attendance)
            ->color('lime')
            ->backgroundColor('rgba(0,255,0,0.1)')
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
                        'text' => 'Future Dates'
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Predicted Attendance'
                    ]
                ]
            ]
        ]);
    }
}
