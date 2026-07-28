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

        $message->loadMissing('attachment');
        $attachment = $message->attachment;

        abort_unless($attachment, 404);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Cache-Control' => 'private, max-age=3600',
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
            $attachment->isPreviewableImage() ? 'inline' : 'attachment',
        );
    }
}
