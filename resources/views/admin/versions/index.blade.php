@extends('admin.layouts.app')

@section('title', 'مدیریت نسخه‌ها')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">مدیریت نسخه‌ها</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.versions.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> افزودن نسخه جدید
                        </a>
                        <a href="{{ route('admin.versions.statistics') }}" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> آمار
                        </a>
                        <form method="POST" action="{{ route('admin.versions.clear-cache') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('آیا مطمئن هستید؟')">
                                <i class="fas fa-trash"></i> پاک کردن کش
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-code-branch"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">کل نسخه‌ها</span>
                                    <span class="info-box-number">{{ $stats['total_versions'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">نسخه‌های فعال</span>
                                    <span class="info-box-number">{{ $stats['active_versions'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">به‌روزرسانی‌های اجباری</span>
                                    <span class="info-box-number">{{ $stats['forced_updates'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-star"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">آخرین نسخه‌ها</span>
                                    <span class="info-box-number">{{ $stats['latest_versions'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>پلتفرم</label>
                                    <select name="platform" class="form-control">
                                        <option value="">همه پلتفرم‌ها</option>
                                        <option value="android" {{ request('platform') == 'android' ? 'selected' : '' }}>اندروید</option>
                                        <option value="ios" {{ request('platform') == 'ios' ? 'selected' : '' }}>iOS</option>
                                        <option value="web" {{ request('platform') == 'web' ? 'selected' : '' }}>وب</option>
                                        <option value="all" {{ request('platform') == 'all' ? 'selected' : '' }}>همه</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>نوع به‌روزرسانی</label>
                                    <select name="update_type" class="form-control">
                                        <option value="">همه انواع</option>
                                        <option value="optional" {{ request('update_type') == 'optional' ? 'selected' : '' }}>اختیاری</option>
                                        <option value="forced" {{ request('update_type') == 'forced' ? 'selected' : '' }}>اجباری</option>
                                        <option value="maintenance" {{ request('update_type') == 'maintenance' ? 'selected' : '' }}>تعمیرات</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>وضعیت</label>
                                    <select name="status" class="form-control">
                                        <option value="">همه وضعیت‌ها</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                                        <option value="latest" {{ request('status') == 'latest' ? 'selected' : '' }}>آخرین نسخه</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>جستجو</label>
                                    <input type="text" name="search" class="form-control" placeholder="جستجو در نسخه، عنوان..." value="{{ request('search') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">فیلتر</button>
                                <a href="{{ route('admin.versions.index') }}" class="btn btn-secondary">پاک کردن فیلتر</a>
                            </div>
                        </div>
                    </form>

                    <!-- Versions Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>نسخه</th>
                                    <th>پلتفرم</th>
                                    <th>نوع</th>
                                    <th>عنوان</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ انتشار</th>
                                    <th>اولویت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($versions as $version)
                                <tr>
                                    <td>
                                        <strong>{{ $version->version }}</strong>
                                        @if($version->build_number)
                                            <br><small class="text-muted">Build: {{ $version->build_number }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $version->platform_label }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $version->update_type == 'forced' ? 'badge-danger' : ($version->update_type == 'optional' ? 'badge-primary' : 'badge-warning') }}">
                                            {{ $version->update_type_label }}
                                        </span>
                                    </td>
                                    <td>{{ $version->title }}</td>
                                    <td>
                                        <span class="badge {{ $version->status_badge_class }}">
                                            {{ $version->status_label }}
                                        </span>
                                    </td>
                                    <td>{{ $version->formatted_release_date }}</td>
                                    <td>{{ $version->priority }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.versions.show', $version) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.versions.edit', $version) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.versions.toggle-active', $version) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $version->is_active ? 'btn-secondary' : 'btn-success' }}" 
                                                        title="{{ $version->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                                    <i class="fas {{ $version->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                            </form>
                                            @if(!$version->is_latest)
                                            <form method="POST" action="{{ route('admin.versions.set-latest', $version) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary" title="تنظیم به عنوان آخرین نسخه">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.versions.destroy', $version) }}" class="d-inline" 
                                                  onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این نسخه را حذف کنید؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">هیچ نسخه‌ای یافت نشد</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $versions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh statistics every 30 seconds
    setInterval(function() {
        // You can add AJAX call here to refresh statistics
    }, 30000);
</script>
@endpush
