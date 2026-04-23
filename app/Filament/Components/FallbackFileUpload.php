<?php

namespace App\Filament\Components;

use App\Support\StorageFallback;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FallbackFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fetchFileInformation(false);

        $this->getUploadedFileUsing(
            fn (BaseFileUpload $component, string $file, string | array | null $storedFileNames) => StorageFallback::fileInfo(
                $component,
                $file,
                $storedFileNames,
            ),
        );

        // Use streaming so uploads work when Livewire temp files are stored on S3
        $this->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
            $disk = Storage::disk($this->getDiskName());
            $filename = $this->getUploadedFileNameForStorage($file);
            $directory = trim($this->getDirectory() ?? '', '/');
            $path = $directory !== '' ? $directory . '/' . $filename : $filename;

            $stream = $file->readStream();
            $disk->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $path;
        });
    }
}
