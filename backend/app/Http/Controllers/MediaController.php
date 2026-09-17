<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function newsCover(string $filename): BinaryFileResponse
    {
        abort_unless($filename === basename($filename), 404);
        $path = 'news-covers/'.$filename;
        $candidates = [
            Storage::disk('public')->path($path),
            // Backward compatibility for covers uploaded before public uploads
            // were moved away from storage/app/public on shared hosting.
            storage_path('app/public/'.$path),
        ];
        $absolutePath = collect($candidates)->first(
            static fn (string $candidate): bool => is_file($candidate),
        );

        abort_unless($absolutePath !== null, 404);
        abort_unless(is_readable($absolutePath), 403);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
