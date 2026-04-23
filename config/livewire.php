<?php

return [
    'temporary_file_upload' => [
        /*
         * Keep Livewire temporary uploads on the application server so
         * the browser does not perform direct cross-origin S3 uploads.
         */
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 'local'),
        'directory' => env('LIVEWIRE_UPLOAD_DIRECTORY', 'livewire-tmp'),
    ],
];
