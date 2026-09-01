<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteFileObjects implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $disk, public array $files)
    {
    }


    public function handle(): void
    {
        Storage::disk($this->disk)->delete($this->files);
    }
}
