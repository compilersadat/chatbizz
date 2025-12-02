<?php

namespace App\Filament\Components;

use App\Support\StorageFallback;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;

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
    }
}
