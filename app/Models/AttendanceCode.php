<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class AttendanceCode extends Model
{
    use HasUuidRouteKey;

    protected $fillable = [
        'uuid',
        'teacher_id',
        'section_id',
        'code',
        'qr_code_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'qr_code_url',
    ];


    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }


    public static function generateUniqueCode()
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }


    public function generateQrCode()
    {
        $url = route('public.attendance.show', ['code' => $this->code]);
        
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($url);

        $filename = 'qr-codes/attendance-' . $this->code . '-' . time() . '.svg';
        Storage::disk('public')->put($filename, $qrCode);
        
        $publicPath = public_path($filename);
        $publicDir = dirname($publicPath);
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        file_put_contents($publicPath, $qrCode);

        $this->qr_code_path = $filename;
        $this->save();

        return $filename;
    }


    public function isValid()
    {
        return $this->is_active;
    }


    public function deactivate()
    {
        $this->is_active = false;
        $this->save();

        if ($this->qr_code_path) {
            if (Storage::disk('public')->exists($this->qr_code_path)) {
                Storage::disk('public')->delete($this->qr_code_path);
            }
            $publicPath = public_path($this->qr_code_path);
            if (file_exists($publicPath)) {
                unlink($publicPath);
            }
        }
    }

    public static function getActiveCodeForTeacher($teacherId, $sectionId = null)
    {
        $query = self::where('teacher_id', $teacherId)
            ->where('is_active', true);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        return $query->first();
    }

    public static function createForTeacher($teacherId, $sectionId = null, $durationMinutes = null)
    {
        $existingCodes = self::where('teacher_id', $teacherId)
            ->where('is_active', true);
        
        if ($sectionId) {
            $existingCodes->where('section_id', $sectionId);
        }
        
        $existingCodes->each(function ($code) {
            $code->deactivate();
        });

        $attendanceCode = self::create([
            'teacher_id' => $teacherId,
            'section_id' => $sectionId,
            'code' => self::generateUniqueCode(),
            'is_active' => true,
        ]);

        $attendanceCode->generateQrCode();

        return $attendanceCode;
    }

    public static function validateCode($code)
    {
        $attendanceCode = self::where('code', $code)
            ->where('is_active', true)
            ->first();

        return $attendanceCode;
    }

    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code_path) {
            $publicPath = public_path($this->qr_code_path);
            if (file_exists($publicPath)) {
                return url('/public-storage/' . ltrim($this->qr_code_path, '/'));
            }
            return Storage::disk('public')->url($this->qr_code_path);
        }
        return null;
    }
}
