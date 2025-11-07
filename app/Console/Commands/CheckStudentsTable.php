<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStudentsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:students-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check students table structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Students Table Structure:');
        $this->info(str_repeat('=', 80));
        
        try {
            $columns = DB::select('SHOW COLUMNS FROM students');
            
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
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
