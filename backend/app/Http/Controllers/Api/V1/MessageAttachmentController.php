<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function __invoke(Message $message): StreamedResponse
    {
        Gate::authorize('view', $message);

        $message->loadMissing('attachment.file');
        $file = $message->attachment?->file;

        abort_unless($file, 404);

        $disk = Storage::disk($file->disk);
        $path = $file->servedPath();

        abort_unless($disk->exists($path), 404);

        return $disk->response(
            $path,
            $file->original_name,
            [
                'Cache-Control' => 'private, max-age=3600',
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
            $file->isImage() ? 'inline' : 'attachment',
        );
    }
}
