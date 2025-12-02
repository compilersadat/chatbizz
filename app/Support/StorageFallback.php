<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;
use Throwable;

class StorageFallback
{
    /**
     * Resolve the correct URL for a stored file, checking the configured disk
     * first and falling back to the public disk for legacy files.
     */
    public static function url(?string $path, ?string $primaryDisk = null, ?string $fallbackDisk = 'public'): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        foreach (self::diskOrder($primaryDisk, $fallbackDisk) as $diskName) {
            $disk = Storage::disk($diskName);

            try {
                if (! $disk->exists($path)) {
                    continue;
                }
            } catch (UnableToCheckFileExistence) {
                continue;
            } catch (Throwable) {
                continue;
            }

            try {
                return $disk->url($path);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Build FileUpload state with disk fallback so existing local files still show previews.
     */
    public static function fileInfo(
        BaseFileUpload $component,
        string $file,
        string | array | null $storedFileNames = null,
        ?string $fallbackDisk = 'public',
    ): ?array {
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $name = $component->isMultiple()
                ? ($storedFileNames[$file] ?? null)
                : ($storedFileNames ?? null);

            return [
                'name' => $name ?? basename($file),
                'size' => 0,
                'type' => null,
                'url' => $file,
            ];
        }

        foreach (self::diskOrder($component->getDiskName(), $fallbackDisk) as $diskName) {
            $disk = Storage::disk($diskName);

            try {
                if (! $disk->exists($file)) {
                    continue;
                }
            } catch (UnableToCheckFileExistence) {
                continue;
            } catch (Throwable) {
                continue;
            }

            $name = $component->isMultiple()
                ? ($storedFileNames[$file] ?? null)
                : ($storedFileNames ?? null);

            $url = null;

            if ($component->getVisibility() === 'private') {
                try {
                    $url = $disk->temporaryUrl($file, now()->addMinutes(5));
                } catch (Throwable) {
                    $url = null;
                }
            }

            $url ??= $disk->url($file);

            $size = $component->shouldFetchFileInformation() ? $disk->size($file) : 0;
            $type = $component->shouldFetchFileInformation() ? $disk->mimeType($file) : null;

            return [
                'name' => $name ?? basename($file),
                'size' => $size,
                'type' => $type,
                'url' => $url,
            ];
        }

        return null;
    }

    /**
     * @return array<string>
     */
    private static function diskOrder(?string $primaryDisk, ?string $fallbackDisk): array
    {
        $disks = [
            $primaryDisk ?? config('filesystems.default'),
            $fallbackDisk,
        ];

        return array_values(array_unique(array_filter($disks)));
    }
}
