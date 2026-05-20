<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentObserver
{
    /**
     * Handle the Student "updating" event.
     * This fires before the update is committed to the database.
     */
    public function updating(Student $student)
    {
        // Check if the LRN (id_no) is being changed
        if ($student->isDirty('id_no')) {
            $oldLrn = $student->getOriginal('id_no');
            $newLrn = $student->id_no;
            
            // Only update password if student is still using the default password (old LRN)
            if ($student->password && Hash::check($oldLrn, $student->password)) {
                // Student is still using the LRN as password, so update it to the new LRN
                $student->password = Hash::make($newLrn);
                
                \Log::info('Student LRN updated with password sync', [
                    'student_id' => $student->id,
                    'old_lrn' => $oldLrn,
                    'new_lrn' => $newLrn,
                    'action' => 'password_updated_automatically'
                ]);
            } else {
                // Student has changed their password, don't auto-update
                \Log::info('Student LRN updated - password NOT synced (custom password detected)', [
                    'student_id' => $student->id,
                    'old_lrn' => $oldLrn,
                    'new_lrn' => $newLrn,
                    'action' => 'password_kept_unchanged',
                    'note' => 'Student will need to update password on next login if it was LRN-based'
                ]);
            }
        }
    }

    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student)
    {
        // Ensure password is set on creation if LRN exists but password is null
        if ($student->id_no && !$student->password) {
            $student->update([
                'password' => Hash::make($student->id_no),
                'password_changed' => false,
            ]);
            
            \Log::info('Student created with default password', [
                'student_id' => $student->id,
                'lrn' => $student->id_no,
            ]);
        }
    }
}
