<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAvatarController extends Controller
{
    public function __invoke(Request $request, User $user): StreamedResponse
    {
        $file = $user->avatar;

        abort_unless($file, 404);

        $disk = Storage::disk($file->disk);
        $path = $file->servedPath();

        abort_unless($disk->exists($path), 404);

        $canonical = $request->query('v') === (string) $file->id;

        return $disk->response(
            $path,
            $file->original_name,
            [
                'Cache-Control' => $canonical
                    ? 'public, max-age=31536000, immutable'
                    : 'no-cache',
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}
