<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class AudioProcessingService
{
    protected $supportedFormats = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac'];
    protected $outputFormat = 'mp3';
    protected $defaultBitrate = 192;
    protected $tempPath;

    public function __construct()
    {
        $this->tempPath = storage_path('app/temp/audio');
        $this->ensureTempDirectory();
    }

    /**
     * Process audio file with various options
     */
    public function processAudio(string $filePath, array $options = []): array
    {
        try {
            $originalPath = $filePath;
            $fileInfo = pathinfo($filePath);
            $originalFormat = strtolower($fileInfo['extension']);

            // Validate format
            if (!in_array($originalFormat, $this->supportedFormats)) {
                throw new \InvalidArgumentException("فرمت صوتی پشتیبانی نمی‌شود: {$originalFormat}");
            }

            $options = array_merge([
                'convert_format' => $this->outputFormat,
                'bitrate' => $this->defaultBitrate,
                'extract_metadata' => true,
                'normalize' => false,
                'fade_in' => 0,
                'fade_out' => 0,
                'trim_start' => 0,
                'trim_end' => 0,
                'volume' => 1.0,
                'quality' => 'high'
            ], $options);

            $result = [
                'original_file' => $filePath,
                'processed_file' => null,
                'metadata' => [],
                'processing_info' => [],
                'success' => false
            ];

            // Extract metadata first
            if ($options['extract_metadata']) {
                $result['metadata'] = $this->extractMetadata($filePath);
            }

            // Check if conversion is needed
            $needsConversion = $originalFormat !== $options['convert_format'];
            $needsProcessing = $options['normalize'] || $options['fade_in'] > 0 || 
                              $options['fade_out'] > 0 || $options['trim_start'] > 0 || 
                              $options['trim_end'] > 0 || $options['volume'] !== 1.0;

            if (!$needsConversion && !$needsProcessing) {
                $result['processed_file'] = $filePath;
                $result['success'] = true;
                return $result;
            }

            // Generate output filename
            $outputFilename = $this->generateOutputFilename($fileInfo['filename'], $options['convert_format']);
            $outputPath = $this->tempPath . '/' . $outputFilename;

            // Build FFmpeg command
            $command = $this->buildFFmpegCommand($filePath, $outputPath, $options);

            // Execute processing
            $processResult = Process::run($command);
            
            if (!$processResult->successful()) {
                throw new \RuntimeException("خطا در پردازش فایل صوتی: " . $processResult->errorOutput());
            }

            $result['processed_file'] = $outputPath;
            $result['processing_info'] = [
                'command' => $command,
                'execution_time' => $processResult->elapsedTime(),
                'output_size' => filesize($outputPath),
                'processing_options' => $options
            ];
            $result['success'] = true;

            Log::info('Audio processing completed', [
                'original_file' => $filePath,
                'processed_file' => $outputPath,
                'options' => $options
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Audio processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'options' => $options
            ]);

            throw $e;
        }
    }

    /**
     * Extract metadata from audio file
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            $command = "ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($filePath);
            $processResult = Process::run($command);

            if (!$processResult->successful()) {
                throw new \RuntimeException("خطا در استخراج متادیتا: " . $processResult->errorOutput());
            }

            $metadata = json_decode($processResult->output(), true);
            
            return $this->parseMetadata($metadata);

        } catch (\Exception $e) {
            Log::error('Metadata extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Convert audio format
     */
    public function convertFormat(string $filePath, string $targetFormat, int $bitrate = null): string
    {
        $bitrate = $bitrate ?? $this->defaultBitrate;
        
        return $this->processAudio($filePath, [
            'convert_format' => $targetFormat,
            'bitrate' => $bitrate,
            'extract_metadata' => false
        ])['processed_file'];
    }

    /**
     * Normalize audio volume
     */
    public function normalizeAudio(string $filePath): string
    {
        return $this->processAudio($filePath, [
            'normalize' => true,
            'extract_metadata' => false
        ])['processed_file'];
    }

    /**
     * Trim audio file
     */
    public function trimAudio(string $filePath, int $startSeconds, int $endSeconds = null): string
    {
        $options = [
            'trim_start' => $startSeconds,
            'extract_metadata' => false
        ];

        if ($endSeconds !== null) {
            $options['trim_end'] = $endSeconds;
        }

        return $this->processAudio($filePath, $options)['processed_file'];
    }

    /**
     * Add fade effects
     */
    public function addFadeEffects(string $filePath, int $fadeIn = 0, int $fadeOut = 0): string
    {
        return $this->processAudio($filePath, [
            'fade_in' => $fadeIn,
            'fade_out' => $fadeOut,
            'extract_metadata' => false
        ])['processed_file'];
    }

    /**
     * Adjust volume
     */
    public function adjustVolume(string $filePath, float $volumeMultiplier): string
    {
        return $this->processAudio($filePath, [
            'volume' => $volumeMultiplier,
            'extract_metadata' => false
        ])['processed_file'];
    }

    /**
     * Get audio duration
     */
    public function getDuration(string $filePath): float
    {
        $metadata = $this->extractMetadata($filePath);
        return $metadata['duration'] ?? 0;
    }

    /**
     * Get audio file size
     */
    public function getFileSize(string $filePath): int
    {
        return filesize($filePath);
    }

    /**
     * Validate audio file
     */
    public function validateAudioFile(string $filePath): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];

        try {
            // Check if file exists
            if (!file_exists($filePath)) {
                $result['errors'][] = 'فایل وجود ندارد';
                return $result;
            }

            // Check file size
            $fileSize = $this->getFileSize($filePath);
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($fileSize > $maxSize) {
                $result['errors'][] = 'حجم فایل بیش از حد مجاز است';
            }

            // Check format
            $fileInfo = pathinfo($filePath);
            $format = strtolower($fileInfo['extension']);
            if (!in_array($format, $this->supportedFormats)) {
                $result['errors'][] = 'فرمت فایل پشتیبانی نمی‌شود';
            }

            // Extract metadata for validation
            $metadata = $this->extractMetadata($filePath);
            if (empty($metadata)) {
                $result['errors'][] = 'فایل صوتی معتبر نیست';
            } else {
                $result['info'] = $metadata;
                
                // Check duration
                if ($metadata['duration'] < 1) {
                    $result['warnings'][] = 'مدت زمان فایل خیلی کوتاه است';
                } elseif ($metadata['duration'] > 3600) { // 1 hour
                    $result['warnings'][] = 'مدت زمان فایل خیلی طولانی است';
                }

                // Check bitrate
                if ($metadata['bitrate'] < 64) {
                    $result['warnings'][] = 'کیفیت صدا پایین است';
                }
            }

            $result['valid'] = empty($result['errors']);

        } catch (\Exception $e) {
            $result['errors'][] = 'خطا در اعتبارسنجی فایل: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles(array $filePaths = []): int
    {
        $cleanedCount = 0;

        if (empty($filePaths)) {
            // Clean all temp files older than 1 hour
            $files = glob($this->tempPath . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < time() - 3600) {
                    unlink($file);
                    $cleanedCount++;
                }
            }
        } else {
            foreach ($filePaths as $filePath) {
                if (file_exists($filePath) && strpos($filePath, $this->tempPath) === 0) {
                    unlink($filePath);
                    $cleanedCount++;
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(): array
    {
        $tempFiles = glob($this->tempPath . '/*');
        $totalSize = 0;
        $fileCount = 0;

        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }

        return [
            'temp_files_count' => $fileCount,
            'temp_files_size' => $totalSize,
            'temp_files_size_mb' => round($totalSize / 1024 / 1024, 2),
            'supported_formats' => $this->supportedFormats,
            'default_bitrate' => $this->defaultBitrate,
            'output_format' => $this->outputFormat
        ];
    }

    /**
     * Build FFmpeg command
     */
    protected function buildFFmpegCommand(string $inputPath, string $outputPath, array $options): string
    {
        $command = 'ffmpeg -i ' . escapeshellarg($inputPath);

        // Add input options
        if ($options['trim_start'] > 0) {
            $command .= ' -ss ' . $options['trim_start'];
        }

        // Add processing options
        $filters = [];

        if ($options['normalize']) {
            $filters[] = 'loudnorm';
        }

        if ($options['volume'] !== 1.0) {
            $filters[] = 'volume=' . $options['volume'];
        }

        if ($options['fade_in'] > 0) {
            $filters[] = 'afade=t=in:d=' . $options['fade_in'];
        }

        if ($options['fade_out'] > 0) {
            $filters[] = 'afade=t=out:d=' . $options['fade_out'];
        }

        if (!empty($filters)) {
            $command .= ' -af ' . implode(',', $filters);
        }

        // Add output options
        $command .= ' -f ' . $options['convert_format'];
        $command .= ' -b:a ' . $options['bitrate'] . 'k';

        if ($options['convert_format'] === 'mp3') {
            $command .= ' -acodec libmp3lame';
        }

        // Add quality settings
        if ($options['quality'] === 'high') {
            $command .= ' -q:a 0';
        } elseif ($options['quality'] === 'medium') {
            $command .= ' -q:a 2';
        } else {
            $command .= ' -q:a 5';
        }

        // Add duration limit if trim_end is set
        if ($options['trim_end'] > 0) {
            $command .= ' -t ' . ($options['trim_end'] - $options['trim_start']);
        }

        $command .= ' -y ' . escapeshellarg($outputPath);

        return $command;
    }

    /**
     * Parse metadata from FFprobe output
     */
    protected function parseMetadata(array $rawMetadata): array
    {
        $metadata = [
            'duration' => 0,
            'bitrate' => 0,
            'sample_rate' => 0,
            'channels' => 0,
            'format' => '',
            'title' => '',
            'artist' => '',
            'album' => '',
            'year' => '',
            'genre' => ''
        ];

        if (isset($rawMetadata['format'])) {
            $format = $rawMetadata['format'];
            $metadata['duration'] = floatval($format['duration'] ?? 0);
            $metadata['bitrate'] = intval($format['bit_rate'] ?? 0);
            $metadata['format'] = $format['format_name'] ?? '';

            // Parse tags
            if (isset($format['tags'])) {
                $tags = $format['tags'];
                $metadata['title'] = $tags['title'] ?? '';
                $metadata['artist'] = $tags['artist'] ?? '';
                $metadata['album'] = $tags['album'] ?? '';
                $metadata['year'] = $tags['date'] ?? '';
                $metadata['genre'] = $tags['genre'] ?? '';
            }
        }

        if (isset($rawMetadata['streams'][0])) {
            $stream = $rawMetadata['streams'][0];
            $metadata['sample_rate'] = intval($stream['sample_rate'] ?? 0);
            $metadata['channels'] = intval($stream['channels'] ?? 0);
        }

        return $metadata;
    }

    /**
     * Generate output filename
     */
    protected function generateOutputFilename(string $originalFilename, string $format): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
        return $baseName . '_processed_' . $timestamp . '.' . $format;
    }

    /**
     * Ensure temp directory exists
     */
    protected function ensureTempDirectory(): void
    {
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }
}
