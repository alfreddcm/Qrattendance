<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class AttendanceForecastChart extends Chart
{
    public function __construct(array $labels = [], array $forecast = [])
    {
        parent::__construct();
        
        // Handle empty data
        if (empty($labels) || empty($forecast)) {
            $labels = ['No Data'];
            $forecast = [0];
        }
        
        $this->labels($labels);
        $this->dataset('Forecast', 'line', $forecast)
            ->color('rgba(255,99,132,0.7)')
            ->backgroundColor('rgba(255,99,132,0.2)')
            ->fill(false);
        
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
                        'text' => 'Forecasted Attendance'
                    ]
                ]
            ]
        ]);
    }
}
