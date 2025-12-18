@extends('admin.layouts.app')

@section('title', 'App Versions Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">App Versions Management</h3>
                    <a href="{{ route('admin.app-versions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Version
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('admin.app-versions.index') }}" class="form-inline">
                                <div class="form-group mr-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search versions..." 
                                           value="{{ request('search') }}">
                                </div>
                                
                                <div class="form-group mr-3">
                                    <select name="platform" class="form-control">
                                        <option value="">All Platforms</option>
                                        <option value="android" {{ request('platform') == 'android' ? 'selected' : '' }}>Android</option>
                                        <option value="ios" {{ request('platform') == 'ios' ? 'selected' : '' }}>iOS</option>
                                        <option value="both" {{ request('platform') == 'both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-3">
                                    <select name="update_type" class="form-control">
                                        <option value="">All Types</option>
                                        <option value="optional" {{ request('update_type') == 'optional' ? 'selected' : '' }}>Optional</option>
                                        <option value="force" {{ request('update_type') == 'force' ? 'selected' : '' }}>Force Update</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-3">
                                    <select name="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="latest" {{ request('status') == 'latest' ? 'selected' : '' }}>Latest</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-outline-primary">Filter</button>
                                <a href="{{ route('admin.app-versions.index') }}" class="btn btn-outline-secondary ml-2">Clear</a>
                            </form>
                        </div>
                    </div>

                    <!-- Versions Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Platform</th>
                                    <th>Version</th>
                                    <th>Build Number</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Download URL</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($versions as $version)
                                <tr>
                                    <td>{{ $version->id }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $version->platform_display_name }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $version->version }}</strong>
                                        @if($version->is_latest)
                                            <span class="badge badge-success ml-1">Latest</span>
                                        @endif
                                    </td>
                                    <td>{{ $version->build_number ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $version->update_type === 'force' ? 'badge-danger' : 'badge-warning' }}">
                                            {{ $version->update_type_display_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $version->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $version->status_display_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $version->download_url }}" target="_blank" class="text-primary">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a>
                                    </td>
                                    <td>{{ $version->creator->first_name ?? 'System' }}</td>
                                    <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.app-versions.show', $version) }}" 
                                               class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.app-versions.edit', $version) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            @if(!$version->is_latest)
                                            <form method="POST" action="{{ route('admin.app-versions.set-latest', $version) }}" 
                                                  class="d-inline" onsubmit="return confirm('Set this as latest version?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Set as Latest">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </form>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('admin.app-versions.toggle-active', $version) }}" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $version->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" 
                                                        title="{{ $version->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="fas {{ $version->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="{{ route('admin.app-versions.destroy', $version) }}" 
                                                  class="d-inline" onsubmit="return confirm('Are you sure you want to delete this version?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">No app versions found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $versions->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
