<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingLogoController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $logoPath = BusinessSetting::query()->value('logo_path');

        abort_unless($logoPath && Storage::disk('public')->exists($logoPath), 404);

        return response()->file(
            Storage::disk('public')->path($logoPath),
            ['Cache-Control' => 'public, max-age=3600'],
        );
    }
}
