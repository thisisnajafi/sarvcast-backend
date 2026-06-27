<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Sponsor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SponsorController extends Controller
{
    public function apiIndex(Request $request)
    {
        $query = Sponsor::query()->withCount('stories');

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active') && $request->is_active !== '' && $request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $request->get('sort_by', 'display_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSort = ['name', 'display_order', 'created_at', 'stories_count'];
        if (in_array($sortBy, $allowedSort, true)) {
            $query->orderBy($sortBy, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->ordered();
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        return AdminApiResponse::paginated($query->paginate($perPage));
    }

    public function apiStore(Request $request)
    {
        $validator = $this->makeValidator($request->all(), true);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $this->prepareAttributes($request, $validator->validated());
        $data['logo_path'] = $this->storeLogo($request->file('logo'));

        $sponsor = Sponsor::create($data);

        return AdminApiResponse::success(
            $sponsor->loadCount('stories'),
            'Sponsor created successfully',
            201
        );
    }

    public function apiShow(Sponsor $sponsor)
    {
        return AdminApiResponse::success($sponsor->loadCount('stories'));
    }

    public function apiUpdate(Request $request, Sponsor $sponsor)
    {
        $validator = $this->makeValidator($request->all(), false);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $this->prepareAttributes($request, $validator->validated());

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile($sponsor->logo_path);
            $data['logo_path'] = $this->storeLogo($request->file('logo'));
        }

        $sponsor->update($data);

        return AdminApiResponse::success(
            $sponsor->fresh()->loadCount('stories'),
            'Sponsor updated successfully'
        );
    }

    public function apiDestroy(Sponsor $sponsor)
    {
        $sponsor->update(['is_active' => false]);

        return AdminApiResponse::okMessage('Sponsor deactivated successfully');
    }

    public function apiReplaceLogo(Request $request, Sponsor $sponsor)
    {
        $validator = Validator::make($request->all(), [
            'logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $this->deleteLogoFile($sponsor->logo_path);
        $sponsor->update([
            'logo_path' => $this->storeLogo($request->file('logo')),
        ]);

        return AdminApiResponse::success($sponsor->fresh()->loadCount('stories'), 'Logo updated successfully');
    }

    public function apiStories(Sponsor $sponsor)
    {
        $stories = $sponsor->stories()
            ->with('category:id,name')
            ->orderByDesc('updated_at')
            ->paginate(min((int) request('per_page', 20), 100));

        return AdminApiResponse::paginated($stories);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(array $data, bool $isCreate): \Illuminate\Validation\Validator
    {
        $logoRules = $isCreate
            ? ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200']
            : ['sometimes', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'];

        return Validator::make($data, [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'logo' => $logoRules,
            'tagline' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'instagram_handle' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_label' => ['nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareAttributes(Request $request, array $validated): array
    {
        unset($validated['logo']);

        if (array_key_exists('instagram_handle', $validated) && $validated['instagram_handle'] !== null) {
            $validated['instagram_handle'] = ltrim((string) $validated['instagram_handle'], '@');
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }

    private function storeLogo(\Illuminate\Http\UploadedFile $file): string
    {
        return $file->store('sponsors/logos', 'public');
    }

    private function deleteLogoFile(?string $path): void
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function validationError(\Illuminate\Validation\Validator $validator)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'error' => 'VALIDATION_ERROR',
            'errors' => $validator->errors(),
        ], 422);
    }
}
