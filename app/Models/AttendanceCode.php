<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class AttendanceCode extends Model
{
    protected $fillable = [
        'teacher_id',
        'section_id',
        'code',
        'qr_code_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Belongs to a teacher (User)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relationship: Belongs to a section
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Generate a unique 6-digit code
     */
    public static function generateUniqueCode()
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate QR code image and save it
     */
    public function generateQrCode()
    {
        $url = url('/public/attendance?code=' . $this->code);
        
        // Generate QR code (using SVG format like student QR codes)
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($url);

        // Save to storage
        $filename = 'qr-codes/attendance-' . $this->code . '-' . time() . '.svg';
        Storage::disk('public')->put($filename, $qrCode);

        // Update model
        $this->qr_code_path = $filename;
        $this->save();

        return $filename;
    }

    /**
     * Check if code is valid (only checks if active, no expiration)
     */
    public function isValid()
    {
        return $this->is_active;
    }

    /**
     * Deactivate this code
     */
    public function deactivate()
    {
        $this->is_active = false;
        $this->save();

        // Optionally delete QR code file
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            Storage::disk('public')->delete($this->qr_code_path);
        }
    }

    /**
     * Get active code for a teacher (no expiration check)
     */
    public static function getActiveCodeForTeacher($teacherId, $sectionId = null)
    {
        $query = self::where('teacher_id', $teacherId)
            ->where('is_active', true);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        return $query->first();
    }

    /**
     * Create a new attendance code for a teacher (no expiration)
     */
    public static function createForTeacher($teacherId, $sectionId = null, $durationMinutes = null)
    {
        // Deactivate any existing active codes for this teacher and section
        $existingCodes = self::where('teacher_id', $teacherId)
            ->where('is_active', true);
        
        if ($sectionId) {
            $existingCodes->where('section_id', $sectionId);
        }
        
        $existingCodes->each(function ($code) {
            $code->deactivate();
        });

        // Create new code (no expiration)
        $attendanceCode = self::create([
            'teacher_id' => $teacherId,
            'section_id' => $sectionId,
            'code' => self::generateUniqueCode(),
            'is_active' => true,
        ]);

        // Generate QR code
        $attendanceCode->generateQrCode();

        return $attendanceCode;
    }

    /**
     * Validate and retrieve code (no expiration check)
     */
    public static function validateCode($code)
    {
        $attendanceCode = self::where('code', $code)
            ->where('is_active', true)
            ->first();

        return $attendanceCode;
    }

    /**
     * Get QR code URL
     */
    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code_path) {
            return Storage::disk('public')->url($this->qr_code_path);
        }
        return null;
    }
}
