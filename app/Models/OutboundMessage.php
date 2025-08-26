<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'admin_id',
        'student_id',
        'contact_number',
        'message',
        'message_id',
        'status',
        'recipient_type',
        'recipient_count',
        'last_sent_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_sent_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
