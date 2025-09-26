@extends('admin.layouts.app')

@section('title', 'App Version Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">App Version Details - {{ $appVersion->version }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.app-versions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('admin.app-versions.edit', $appVersion) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">ID:</th>
                                    <td>{{ $appVersion->id }}</td>
                                </tr>
                                <tr>
                                    <th>Platform:</th>
                                    <td>
                                        <span class="badge badge-info">{{ $appVersion->platform_display_name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Version:</th>
                                    <td>
                                        <strong>{{ $appVersion->version }}</strong>
                                        @if($appVersion->is_latest)
                                            <span class="badge badge-success ml-2">Latest</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Build Number:</th>
                                    <td>{{ $appVersion->build_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Update Type:</th>
                                    <td>
                                        <span class="badge {{ $appVersion->update_type === 'force' ? 'badge-danger' : 'badge-warning' }}">
                                            {{ $appVersion->update_type_display_name }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge {{ $appVersion->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $appVersion->status_display_name }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Created By:</th>
                                    <td>{{ $appVersion->creator->first_name ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $appVersion->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At:</th>
                                    <td>{{ $appVersion->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Release Date:</th>
                                    <td>{{ $appVersion->release_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Effective Date:</th>
                                    <td>{{ $appVersion->effective_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Expiry Date:</th>
                                    <td>{{ $appVersion->expiry_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Download URL</h5>
                            <p>
                                <a href="{{ $appVersion->download_url }}" target="_blank" class="text-primary">
                                    <i class="fas fa-external-link-alt"></i> {{ $appVersion->download_url }}
                                </a>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Version Codes</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Min Supported:</th>
                                    <td>{{ $appVersion->min_supported_version_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Target:</th>
                                    <td>{{ $appVersion->target_version_code ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($appVersion->update_message)
                    <div class="row">
                        <div class="col-12">
                            <h5>Update Message</h5>
                            <div class="alert alert-info">
                                {{ $appVersion->update_message }}
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($appVersion->release_notes)
                    <div class="row">
                        <div class="col-12">
                            <h5>Release Notes</h5>
                            <div class="card">
                                <div class="card-body">
                                    {!! nl2br(e($appVersion->release_notes)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if($appVersion->compatibility_requirements)
                    <div class="row">
                        <div class="col-12">
                            <h5>Compatibility Requirements</h5>
                            <div class="card">
                                <div class="card-body">
                                    <pre>{{ json_encode($appVersion->compatibility_requirements, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="card-footer">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.app-versions.edit', $appVersion) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        
                        @if(!$appVersion->is_latest)
                        <form method="POST" action="{{ route('admin.app-versions.set-latest', $appVersion) }}" 
                              class="d-inline" onsubmit="return confirm('Set this as latest version?')">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-star"></i> Set as Latest
                            </button>
                        </form>
                        @endif
                        
                        <form method="POST" action="{{ route('admin.app-versions.toggle-active', $appVersion) }}" 
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn {{ $appVersion->is_active ? 'btn-warning' : 'btn-success' }}">
                                <i class="fas {{ $appVersion->is_active ? 'fa-pause' : 'fa-play' }}"></i> 
                                {{ $appVersion->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('admin.app-versions.destroy', $appVersion) }}" 
                              class="d-inline" onsubmit="return confirm('Are you sure you want to delete this version?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
