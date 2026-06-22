<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * اسم الـ disk المستخدم للملفات الخاصة (هويات، سندات...).
     * يُقرأ من .env — افتراضياً local، وعلى Production يُضبط على s3_private.
     */
    public static function privateDisk(): string
    {
        return env('PRIVATE_STORAGE_DISK', 'private');
    }

    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::privateDisk());
    }

    public static function store(\Illuminate\Http\UploadedFile $file, string $path): string
    {
        return $file->store($path, self::privateDisk());
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    public static function path(string $path): string
    {
        return self::disk()->path($path);
    }

    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }

    public static function response(string $filePath): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $disk = self::privateDisk();

        if ($disk === 'private') {
            return response()->file(Storage::disk($disk)->path($filePath));
        }

        // S3: stream مؤقت
        $stream = Storage::disk($disk)->readStream($filePath);
        $mime   = Storage::disk($disk)->mimeType($filePath);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'        => $mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
        ]);
    }
}
