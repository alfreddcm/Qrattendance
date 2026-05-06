<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Models\Student;

class OutboundMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $student = Student::first();
        if (!$teacher) {
            $this->command->info('No teacher found for OutboundMessagesSeeder');
            return;
        }

        OutboundMessage::create([
            'teacher_id' => $teacher->id,
            'admin_id' => null,
            'recipient_type' => 'individual',
            'recipient_count' => $student ? 1 : 0,
            'student_id' => $student ? $student->id : null,
            'contact_number' => $student ? $student->contact_person_contact : null,
            'message' => 'This is a test message from seeder.',
            'message_id' => null,
            'status' => 'sent',
        ]);
    }
}
