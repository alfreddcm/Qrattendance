<?php

namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class AbsenteeismRatesChart extends Chart
{
    public function __construct(array $labels, array $percentages)
    {
        parent::__construct();
        
        // Debug what's being passed to the chart
        \Log::info('Chart Constructor Data', [
            'labels_received' => $labels,
            'percentages_received' => $percentages,
            'labels_count' => count($labels),
            'percentages_count' => count($percentages)
        ]);
        
        // Handle empty data or mismatched arrays
        if (empty($labels) || empty($percentages) || count($labels) != count($percentages)) {
            $labels = ['No Student Data Available'];
            $percentages = [0];
        }
        
        // Ensure labels are strings and percentages are numeric
        $labels = array_map('strval', $labels);
        $percentages = array_map('floatval', $percentages);
        
        $this->labels($labels);
        $this->dataset('Absenteeism %', 'bar', $percentages)
            ->color('#dc3545')
            ->backgroundColor('#dc3545'); // Red color for absenteeism (negative metric)
        
        // Add chart options for better display of student names
        $this->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Student Names'
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45
                    ]
                ],
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'max' => 100,
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Absenteeism Percentage (%)'
                    ]
                ]
            ]
        ]);
    }
}
