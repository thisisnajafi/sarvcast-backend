<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload image file
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            'directory' => 'nullable|string|max:100',
            'disk' => 'nullable|string|in:local,public,s3',
            'sizes' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('image');
        $directory = $request->get('directory', 'images');
        $disk = $request->get('disk');
        $sizes = $request->get('sizes', []);

        try {
            $result = $this->fileService->uploadImage($file, $directory, $sizes, $disk);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload audio file
     */
    public function uploadAudio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:mp3,wav,m4a,aac,ogg|max:102400', // 100MB max
            'directory' => 'nullable|string|max:100',
            'disk' => 'nullable|string|in:local,public,s3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('audio');
        $directory = $request->get('directory', 'audio');
        $disk = $request->get('disk');

        try {
            $result = $this->fileService->uploadAudio($file, $directory, $disk);

            return response()->json([
                'success' => true,
                'message' => 'Audio uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload audio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload general file
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'directory' => 'nullable|string|max:100',
            'disk' => 'nullable|string|in:local,public,s3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $directory = $request->get('directory', 'uploads');
        $disk = $request->get('disk');

        try {
            $result = $this->fileService->uploadFile($file, $directory, $disk);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'disk' => 'nullable|string|in:local,public,s3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->get('path');
        $disk = $request->get('disk');

        try {
            $result = $this->fileService->deleteFile($path, $disk);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'File deleted successfully' : 'File not found or already deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file info
     */
    public function getFileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'disk' => 'nullable|string|in:local,public,s3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $path = $request->get('path');
        $disk = $request->get('disk');

        try {
            $result = $this->fileService->getFileInfo($path, $disk);

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage configuration
     */
    public function getStorageConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'default_disk' => config('filesystems.default'),
                's3_configured' => $this->fileService->isS3Configured(),
                'available_disks' => ['local', 'public', 's3'],
                'max_file_size' => '100MB',
                'allowed_image_types' => ['jpeg', 'png', 'jpg', 'gif'],
                'allowed_audio_types' => ['mp3', 'wav', 'm4a', 'aac', 'ogg']
            ]
        ]);
    }
}
