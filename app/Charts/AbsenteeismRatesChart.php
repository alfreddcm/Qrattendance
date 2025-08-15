<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class AbsenteeismRatesChart extends Chart
{
    public function __construct(array $labels, array $percentages)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($percentages)) {
            $labels = ['No Data'];
            $percentages = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Attendance %', 'bar', $percentages)
            ->color('purple')
            ->backgroundColor('purple');
        
        // Add chart options for better handling of empty data
        $this->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Students'
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'max' => 100,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Attendance Percentage (%)'
                    ]
                ]
            ]
        ]);
    }
}
