<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class TimePatternsChart extends Chart
{
    public function __construct(array $labels, array $timeIn, array $timeOut)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($timeIn) || empty($timeOut)) {
            $labels = ['No Data'];
            $timeIn = [0];
            $timeOut = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Time In', 'line', $timeIn)
            ->color('blue')
            ->backgroundColor('rgba(0,0,255,0.1)')
            ->fill(true);
        $this->dataset('Time Out', 'line', $timeOut)
            ->color('orange')
            ->backgroundColor('rgba(255,165,0,0.1)')
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
                        'text' => 'Time Period'
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Average Time (Hours)'
                    ]
                ]
            ]
        ]);
    }
}
