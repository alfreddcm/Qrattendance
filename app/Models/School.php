<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    use HasFactory, HasUuidRouteKey;

    protected $fillable = [
        'uuid',
        'school_id',
        'name',
        'address',
        'logo',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'school_id', 'id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'school_id', 'id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        $logoPath = $this->normalizeLogoPath($this->logo);

        if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        // Prefer direct web access when public/storage is available.
        if (is_file(public_path('storage/' . $logoPath))) {
            return asset('storage/' . $logoPath);
        }

        // Fallback for hosting environments where symlinks are unavailable.
        if (Route::has('public.storage.file')) {
            return route('public.storage.file', ['path' => $logoPath]);
        }

        return Storage::disk('public')->url($logoPath);
    }

    private function normalizeLogoPath(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        $normalized = ltrim($logoPath, '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        return $normalized !== '' ? $normalized : null;
    }
}
