<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSchoolYearsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:school-years-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the school_years table structure and data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('School Years Table Structure:');
        $this->info(str_repeat('=', 80));
        
        try {
            // Get table columns
            $columns = DB::select('SHOW COLUMNS FROM school_years');
            
            $this->table(
                ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                array_map(function($column) {
                    return [
                        $column->Field,
                        $column->Type,
                        $column->Null,
                        $column->Key ?? '',
                        $column->Default ?? 'NULL',
                        $column->Extra ?? ''
                    ];
                }, $columns)
            );
            
            // Check if table has data
            $count = DB::table('school_years')->count();
            $this->info("\nTotal records in school_years table: " . $count);
            
            if ($count > 0) {
                $this->info("\nSample data:");
                $records = DB::table('school_years')->limit(3)->get();
                foreach ($records as $record) {
                    $this->line("ID: {$record->id}, Name: " . ($record->name ?? 'NULL') . ", Start: " . ($record->school_year_start ?? 'NULL') . ", End: " . ($record->school_year_end ?? 'NULL'));
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error checking table: ' . $e->getMessage());
        }
    }
}
