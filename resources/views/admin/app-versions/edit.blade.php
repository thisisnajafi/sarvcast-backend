@extends('admin.layouts.app')

@section('title', 'Edit App Version')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit App Version - {{ $appVersion->version }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.app-versions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.app-versions.update', $appVersion) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="platform">Platform <span class="text-danger">*</span></label>
                                    <select name="platform" id="platform" class="form-control @error('platform') is-invalid @enderror" required>
                                        <option value="">Select Platform</option>
                                        <option value="android" {{ old('platform', $appVersion->platform) == 'android' ? 'selected' : '' }}>Android</option>
                                        <option value="ios" {{ old('platform', $appVersion->platform) == 'ios' ? 'selected' : '' }}>iOS</option>
                                        <option value="both" {{ old('platform', $appVersion->platform) == 'both' ? 'selected' : '' }}>Both Platforms</option>
                                    </select>
                                    @error('platform')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="version">Version <span class="text-danger">*</span></label>
                                    <input type="text" name="version" id="version" class="form-control @error('version') is-invalid @enderror" 
                                           value="{{ old('version', $appVersion->version) }}" placeholder="e.g., 1.0.0" required>
                                    @error('version')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="build_number">Build Number</label>
                                    <input type="text" name="build_number" id="build_number" class="form-control @error('build_number') is-invalid @enderror" 
                                           value="{{ old('build_number', $appVersion->build_number) }}" placeholder="e.g., 100">
                                    @error('build_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="update_type">Update Type <span class="text-danger">*</span></label>
                                    <select name="update_type" id="update_type" class="form-control @error('update_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="optional" {{ old('update_type', $appVersion->update_type) == 'optional' ? 'selected' : '' }}>Optional Update</option>
                                        <option value="force" {{ old('update_type', $appVersion->update_type) == 'force' ? 'selected' : '' }}>Force Update</option>
                                    </select>
                                    @error('update_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="download_url">Download URL <span class="text-danger">*</span></label>
                            <input type="url" name="download_url" id="download_url" class="form-control @error('download_url') is-invalid @enderror" 
                                   value="{{ old('download_url', $appVersion->download_url) }}" placeholder="https://example.com/download" required>
                            @error('download_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="website_update_url">Website Update URL</label>
                                    <input type="url" name="website_update_url" id="website_update_url" class="form-control @error('website_update_url') is-invalid @enderror" 
                                           value="{{ old('website_update_url', $appVersion->website_update_url) }}" placeholder="https://example.com/update/website">
                                    @error('website_update_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cafebazaar_update_url">CafeBazaar Update URL</label>
                                    <input type="url" name="cafebazaar_update_url" id="cafebazaar_update_url" class="form-control @error('cafebazaar_update_url') is-invalid @enderror" 
                                           value="{{ old('cafebazaar_update_url', $appVersion->cafebazaar_update_url) }}" placeholder="https://cafebazaar.ir/app/...">
                                    @error('cafebazaar_update_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="myket_update_url">Myket Update URL</label>
                                    <input type="url" name="myket_update_url" id="myket_update_url" class="form-control @error('myket_update_url') is-invalid @enderror" 
                                           value="{{ old('myket_update_url', $appVersion->myket_update_url) }}" placeholder="https://myket.ir/app/...">
                                    @error('myket_update_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="update_message">Update Message</label>
                            <textarea name="update_message" id="update_message" class="form-control @error('update_message') is-invalid @enderror" 
                                      rows="3" placeholder="Custom message to show users">{{ old('update_message', $appVersion->update_message) }}</textarea>
                            @error('update_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="release_notes">Release Notes</label>
                            <textarea name="release_notes" id="release_notes" class="form-control @error('release_notes') is-invalid @enderror" 
                                      rows="5" placeholder="What's new in this version">{{ old('release_notes', $appVersion->release_notes) }}</textarea>
                            @error('release_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_supported_version_code">Min Supported Version Code</label>
                                    <input type="number" name="min_supported_version_code" id="min_supported_version_code" 
                                           class="form-control @error('min_supported_version_code') is-invalid @enderror" 
                                           value="{{ old('min_supported_version_code', $appVersion->min_supported_version_code) }}" min="0">
                                    @error('min_supported_version_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="target_version_code">Target Version Code</label>
                                    <input type="number" name="target_version_code" id="target_version_code" 
                                           class="form-control @error('target_version_code') is-invalid @enderror" 
                                           value="{{ old('target_version_code', $appVersion->target_version_code) }}" min="0">
                                    @error('target_version_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="release_date">Release Date</label>
                                    <input type="date" name="release_date" id="release_date" 
                                           class="form-control @error('release_date') is-invalid @enderror" 
                                           value="{{ old('release_date', $appVersion->release_date?->format('Y-m-d')) }}">
                                    @error('release_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="effective_date">Effective Date</label>
                                    <input type="date" name="effective_date" id="effective_date" 
                                           class="form-control @error('effective_date') is-invalid @enderror" 
                                           value="{{ old('effective_date', $appVersion->effective_date?->format('Y-m-d')) }}">
                                    @error('effective_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="date" name="expiry_date" id="expiry_date" 
                                           class="form-control @error('expiry_date') is-invalid @enderror" 
                                           value="{{ old('expiry_date', $appVersion->expiry_date?->format('Y-m-d')) }}">
                                    @error('expiry_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" 
                                           {{ old('is_active', $appVersion->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_latest" id="is_latest" class="form-check-input" 
                                           {{ old('is_latest', $appVersion->is_latest) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_latest">
                                        Latest Version
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Version
                            </button>
                            <a href="{{ route('admin.app-versions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
