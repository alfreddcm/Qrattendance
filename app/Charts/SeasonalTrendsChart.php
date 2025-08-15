<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class SeasonalTrendsChart extends Chart
{
    public function __construct(array $labels, array $weekly, array $monthly)
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($weekly) || empty($monthly)) {
            $labels = ['No Data'];
            $weekly = [0];
            $monthly = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Weekly', 'line', $weekly)
            ->color('navy')
            ->backgroundColor('rgba(0,0,128,0.1)')
            ->fill(true);
        $this->dataset('Monthly', 'line', $monthly)
            ->color('gray')
            ->backgroundColor('rgba(128,128,128,0.1)')
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
                        'text' => 'Attendance Count'
                    ]
                ]
            ]
        ]);
    }
}
