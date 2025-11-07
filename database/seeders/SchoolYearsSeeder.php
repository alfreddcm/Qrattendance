<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolYear;
use Carbon\Carbon;

class SchoolYearsSeeder extends Seeder
{
    public function run()
    {
        $end = Carbon::today();
        $start = $end->copy()->subMonths(10);

        SchoolYear::create([
            'school_id' => 1,
            'school_year_start' => $start->year,
            'school_year_end' => $end->year,
            'name' => $start->year . '–' . $end->year,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'active',
            'description' => 'Sample school year for seeds',
        ]);
    }
}
