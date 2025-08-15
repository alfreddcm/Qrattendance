<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSeeder extends Seeder
{
    public function run()
    {
        DB::table('schools')->insert([
            'school_id' => '33011',
            'name' => 'San Guillermo Vocational and Industrial High School',
            'address' => 'San Guillermo, Isabela',
            'logo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
