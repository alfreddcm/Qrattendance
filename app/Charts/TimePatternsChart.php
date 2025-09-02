<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class TimePatternsChart extends Chart
{
    public function __construct($timeSlots = null, $amCheckIns = null, $pmCheckOuts = null)
    {
        parent::__construct();
        
        // Default data if nothing is provided
        $defaultTimeSlots = ['6AM-7AM', '7AM-8AM', '8AM-9AM', '9AM-10AM', '10AM-11AM', '11AM-12PM', '1PM-2PM', '2PM-3PM', '3PM-4PM', '4PM-5PM'];
        $defaultAmData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $defaultPmData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        
        // Use provided data or defaults
        $labels = $timeSlots ?: $defaultTimeSlots;
        $amData = $amCheckIns ?: $defaultAmData;
        $pmData = $pmCheckOuts ?: $defaultPmData;
        
        // Ensure all are arrays and have same length
        $labels = is_array($labels) ? array_values($labels) : $defaultTimeSlots;
        $amData = is_array($amData) ? array_values($amData) : $defaultAmData;
        $pmData = is_array($pmData) ? array_values($pmData) : $defaultPmData;
        
        // Convert to integers
        $amData = array_map(function($val) { return is_numeric($val) ? (int)$val : 0; }, $amData);
        $pmData = array_map(function($val) { return is_numeric($val) ? (int)$val : 0; }, $pmData);
        
        // Ensure consistent array lengths
        $maxLength = max(count($labels), count($amData), count($pmData));
        $labels = array_pad($labels, $maxLength, 'Unknown');
        $amData = array_pad($amData, $maxLength, 0);
        $pmData = array_pad($pmData, $maxLength, 0);
        
        // Configure chart
        $this->labels($labels);
        
        $this->dataset('Morning', 'bar', $amData)
            ->color('#3b82f6')
            ->backgroundColor('rgba(59, 130, 246, 0.7)');
            
        $this->dataset('Afternoon', 'bar', $pmData)
            ->color('#f59e0b')
            ->backgroundColor('rgba(245, 158, 11, 0.7)');
            
        // Chart options
        $this->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Students'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Time'
                    ]
                ]
            ]
        ]);
    }
}
