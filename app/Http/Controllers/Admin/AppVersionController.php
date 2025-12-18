<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AppVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AppVersion::with('creator');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%")
                  ->orWhere('update_message', 'like', "%{$search}%");
            });
        }

        // Filter by platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by update type
        if ($request->filled('update_type')) {
            $query->where('update_type', $request->update_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'latest':
                    $query->where('is_latest', true);
                    break;
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $versions = $query->paginate(20);

        return view('admin.app-versions.index', compact('versions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.app-versions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,both',
            'version' => 'required|string|max:20',
            'build_number' => 'nullable|string|max:20',
            'update_type' => 'required|in:optional,force',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'changelog' => 'nullable|string|max:2000',
            'update_notes' => 'nullable|string|max:2000',
            'download_url' => 'required|url|max:500',
            'website_update_url' => 'nullable|url|max:500',
            'cafebazaar_update_url' => 'nullable|url|max:500',
            'myket_update_url' => 'nullable|url|max:500',
            'release_notes' => 'nullable|string|max:2000',
            'update_message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_latest' => 'boolean',
            'min_supported_version_code' => 'nullable|integer|min:0',
            'target_version_code' => 'nullable|integer|min:0',
            'release_date' => 'nullable|date',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for duplicate platform + version
        $existing = AppVersion::where('platform', $request->platform)
            ->where('version', $request->version)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withErrors(['version' => 'This version already exists for the selected platform.'])
                ->withInput();
        }

        // If this is marked as latest, unmark other latest versions for the same platform
        if ($request->boolean('is_latest')) {
            AppVersion::where('platform', $request->platform)
                ->orWhere('platform', 'both')
                ->update(['is_latest' => false]);
        }

        $version = AppVersion::create([
            'platform' => $request->platform,
            'version' => $request->version,
            'build_number' => $request->build_number,
            'update_type' => $request->update_type,
            'title' => $request->title,
            'description' => $request->description,
            'changelog' => $request->changelog,
            'update_notes' => $request->update_notes,
            'download_url' => $request->download_url,
            'website_update_url' => $request->website_update_url,
            'cafebazaar_update_url' => $request->cafebazaar_update_url,
            'myket_update_url' => $request->myket_update_url,
            'release_notes' => $request->release_notes,
            'update_message' => $request->update_message,
            'is_active' => $request->boolean('is_active', true),
            'is_latest' => $request->boolean('is_latest', false),
            'min_supported_version_code' => $request->min_supported_version_code,
            'target_version_code' => $request->target_version_code,
            'release_date' => $request->release_date,
            'effective_date' => $request->effective_date,
            'expiry_date' => $request->expiry_date,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.app-versions.index')
            ->with('success', 'App version created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AppVersion $appVersion)
    {
        $appVersion->load('creator');
        return view('admin.app-versions.show', compact('appVersion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppVersion $appVersion)
    {
        return view('admin.app-versions.edit', compact('appVersion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AppVersion $appVersion)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios,both',
            'version' => 'required|string|max:20',
            'build_number' => 'nullable|string|max:20',
            'update_type' => 'required|in:optional,force',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'changelog' => 'nullable|string|max:2000',
            'update_notes' => 'nullable|string|max:2000',
            'download_url' => 'required|url|max:500',
            'website_update_url' => 'nullable|url|max:500',
            'cafebazaar_update_url' => 'nullable|url|max:500',
            'myket_update_url' => 'nullable|url|max:500',
            'release_notes' => 'nullable|string|max:2000',
            'update_message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_latest' => 'boolean',
            'min_supported_version_code' => 'nullable|integer|min:0',
            'target_version_code' => 'nullable|integer|min:0',
            'release_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check for duplicate platform + version (excluding current)
        $existing = AppVersion::where('platform', $request->platform)
            ->where('version', $request->version)
            ->where('id', '!=', $appVersion->id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withErrors(['version' => 'This version already exists for the selected platform.'])
                ->withInput();
        }

        // If this is marked as latest, unmark other latest versions for the same platform
        if ($request->boolean('is_latest')) {
            AppVersion::where('platform', $request->platform)
                ->orWhere('platform', 'both')
                ->where('id', '!=', $appVersion->id)
                ->update(['is_latest' => false]);
        }

        $appVersion->update([
            'platform' => $request->platform,
            'version' => $request->version,
            'build_number' => $request->build_number,
            'update_type' => $request->update_type,
            'title' => $request->title,
            'description' => $request->description,
            'changelog' => $request->changelog,
            'update_notes' => $request->update_notes,
            'download_url' => $request->download_url,
            'website_update_url' => $request->website_update_url,
            'cafebazaar_update_url' => $request->cafebazaar_update_url,
            'myket_update_url' => $request->myket_update_url,
            'release_notes' => $request->release_notes,
            'update_message' => $request->update_message,
            'is_active' => $request->boolean('is_active', true),
            'is_latest' => $request->boolean('is_latest', false),
            'min_supported_version_code' => $request->min_supported_version_code,
            'target_version_code' => $request->target_version_code,
            'release_date' => $request->release_date,
            'effective_date' => $request->effective_date,
            'expiry_date' => $request->expiry_date,
        ]);

        return redirect()->route('admin.app-versions.index')
            ->with('success', 'App version updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppVersion $appVersion)
    {
        $appVersion->delete();

        return redirect()->route('admin.app-versions.index')
            ->with('success', 'App version deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(AppVersion $appVersion)
    {
        $appVersion->update(['is_active' => !$appVersion->is_active]);

        $status = $appVersion->is_active ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success', "App version {$status} successfully.");
    }

    /**
     * Set as latest version
     */
    public function setLatest(AppVersion $appVersion)
    {
        // Unmark other latest versions for the same platform
        AppVersion::where('platform', $appVersion->platform)
            ->orWhere('platform', 'both')
            ->where('id', '!=', $appVersion->id)
            ->update(['is_latest' => false]);

        // Mark this as latest
        $appVersion->update(['is_latest' => true]);

        return redirect()->back()
            ->with('success', 'App version set as latest successfully.');
    }

    /**
     * API endpoint to check for updates
     */
    public function checkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios',
            'current_version' => 'required|string',
            'current_build_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        $platform = $request->platform;
        $currentVersion = $request->current_version;
        $currentBuildNumber = $request->current_build_number;

        $updateCheck = AppVersion::checkUpdateRequired($platform, $currentVersion, $currentBuildNumber);

        if ($updateCheck['update_required']) {
            $latestVersion = $updateCheck['latest_version'];
            
            return response()->json([
                'success' => true,
                'update_required' => true,
                'force_update' => $updateCheck['force_update'],
                'data' => [
                    'latest_version' => $latestVersion->version,
                    'latest_build_number' => $latestVersion->build_number,
                    'download_url' => $latestVersion->download_url,
                    'update_message' => $latestVersion->update_message,
                    'release_notes' => $latestVersion->release_notes,
                    'update_type' => $latestVersion->update_type,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'update_required' => false,
            'force_update' => false,
            'data' => [
                'latest_version' => $updateCheck['latest_version']?->version,
                'latest_build_number' => $updateCheck['latest_version']?->build_number,
            ]
        ]);
    }

    /**
     * API endpoint to get latest version info
     */
    public function getLatestVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        $platform = $request->platform;
        $latestVersion = AppVersion::getLatestForPlatform($platform);

        if (!$latestVersion) {
            return response()->json([
                'success' => false,
                'message' => 'No version information available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'version' => $latestVersion->version,
                'build_number' => $latestVersion->build_number,
                'platform' => $latestVersion->platform,
                'download_url' => $latestVersion->download_url,
                'update_message' => $latestVersion->update_message,
                'release_notes' => $latestVersion->release_notes,
                'update_type' => $latestVersion->update_type,
                'release_date' => $latestVersion->release_date?->toISOString(),
                'effective_date' => $latestVersion->effective_date?->toISOString(),
            ]
        ]);
    }
}