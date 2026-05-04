<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicStorageController extends Controller
{
    public function show(string $path)
    {
        $normalizedPath = ltrim($path, '/');

        // Guard against path traversal and hidden file access.
        if ($normalizedPath === '' || str_contains($normalizedPath, '..') || str_starts_with($normalizedPath, '.')) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($normalizedPath)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($normalizedPath), [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
