<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileCompressionService
{
    private const MAX_DIMENSION = 1600;
    private const JPEG_QUALITY  = 75;

    /**
     * Simpan file lampiran ke disk. Kalau image (jpg/jpeg/png), di-resize +
     * di-encode ulang sebagai JPEG kualitas 75 supaya ukurannya jauh lebih kecil.
     * PDF/Excel disimpan apa adanya — dikompres ulang secara berarti butuh
     * tool eksternal (Ghostscript dkk) yang tidak tersedia di server ini; XLSX
     * sendiri sebenarnya sudah dalam format ZIP terkompresi.
     *
     * @return array{path:string, size:int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['jpg', 'jpeg', 'png']) && function_exists('imagecreatetruecolor')) {
            return $this->storeCompressedImage($file, $directory);
        }

        $path = $file->store($directory, 'public');
        return ['path' => $path, 'size' => Storage::disk('public')->size($path)];
    }

    private function storeCompressedImage(UploadedFile $file, string $directory): array
    {
        $ext   = strtolower($file->getClientOriginalExtension());
        $image = match ($ext) {
            'png'   => @imagecreatefrompng($file->getRealPath()),
            default => @imagecreatefromjpeg($file->getRealPath()),
        };

        // Kalau gagal decode (file rusak/format tak dikenal), fallback simpan mentah.
        if (!$image) {
            $path = $file->store($directory, 'public');
            return ['path' => $path, 'size' => Storage::disk('public')->size($path)];
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $ratio     = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);
            $newWidth  = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $filename = $directory . '/' . Str::uuid() . '.jpg';
        $fullPath = Storage::disk('public')->path($filename);
        Storage::disk('public')->makeDirectory($directory);

        imagejpeg($image, $fullPath, self::JPEG_QUALITY);
        imagedestroy($image);

        return ['path' => $filename, 'size' => filesize($fullPath)];
    }
}
