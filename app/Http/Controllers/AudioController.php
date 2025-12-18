<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class AudioController extends Controller
{
    /**
     * Serve audio file from storage
     */
    public function serve($path)
    {
        // The path parameter contains the filename
        // Construct the full path: audio/episodes/{filename}
        $fullPath = 'audio/episodes/' . $path;

        // Security: Only allow audio files from episodes directory
        if (!str_starts_with($fullPath, 'audio/episodes/')) {
            abort(404, 'Invalid audio path');
        }

        // Get full path to file in public directory
        $filePath = public_path($fullPath);

        // Check if file exists
        if (!File::exists($filePath)) {
            // Log for debugging
            \Log::warning('Audio file not found', [
                'requested_path' => $path,
                'full_path' => $fullPath,
                'file_path' => $filePath,
                'file_exists' => file_exists($filePath)
            ]);
            abort(404, 'Audio file not found: ' . $path);
        }

        // Get file MIME type
        $mimeType = File::mimeType($filePath);
        
        // Ensure it's an audio file
        if (!str_starts_with($mimeType, 'audio/')) {
            // Try to determine MIME type from extension
            $extension = strtolower(File::extension($filePath));
            $mimeTypes = [
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'ogg' => 'audio/ogg',
                'm4a' => 'audio/mp4',
                'aac' => 'audio/aac',
            ];
            
            if (isset($mimeTypes[$extension])) {
                $mimeType = $mimeTypes[$extension];
            } else {
                $mimeType = 'audio/mpeg'; // Default to MP3
            }
        }

        // Get file size
        $fileSize = File::size($filePath);

        // Handle range requests for audio seeking
        $range = request()->header('Range');
        
        if ($range) {
            // Parse range header (e.g., "bytes=0-1023")
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                $end = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
                $length = $end - $start + 1;
                
                // Validate range
                if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
                    return response('', 416)
                        ->header('Content-Range', "bytes */{$fileSize}");
                }
                
                // Read file chunk
                $file = fopen($filePath, 'rb');
                fseek($file, $start);
                $content = fread($file, $length);
                fclose($file);
                
                return response($content, 206)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Length', $length)
                    ->header('Content-Range', "bytes {$start}-{$end}/{$fileSize}")
                    ->header('Accept-Ranges', 'bytes')
                    ->header('Cache-Control', 'public, max-age=31536000')
                    ->header('X-Content-Type-Options', 'nosniff');
            }
        }

        // Full file response
        $fileContent = File::get($filePath);

        // Return response with proper headers
        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Length', $fileSize)
            ->header('Accept-Ranges', 'bytes')
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}

