@extends('admin.layouts.app')

@section('title', 'مدیریت افراد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">لیست افراد</h3>
                    <a href="{{ route('admin.people.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        افزودن فرد جدید
                    </a>
                </div>

                <!-- Filters -->
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="جستجو در نام یا بیوگرافی..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="role" class="form-select">
                                <option value="">همه نقش‌ها</option>
                                <option value="voice_actor" {{ request('role') == 'voice_actor' ? 'selected' : '' }}>صداپیشه</option>
                                <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>کارگردان</option>
                                <option value="writer" {{ request('role') == 'writer' ? 'selected' : '' }}>نویسنده</option>
                                <option value="producer" {{ request('role') == 'producer' ? 'selected' : '' }}>تهیه‌کننده</option>
                                <option value="author" {{ request('role') == 'author' ? 'selected' : '' }}>نویسنده اصلی</option>
                                <option value="narrator" {{ request('role') == 'narrator' ? 'selected' : '' }}>گوینده</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="verified" class="form-select">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>تأیید شده</option>
                                <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>تأیید نشده</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sort_by" class="form-select">
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>نام</option>
                                <option value="total_stories" {{ request('sort_by') == 'total_stories' ? 'selected' : '' }}>تعداد داستان‌ها</option>
                                <option value="total_episodes" {{ request('sort_by') == 'total_episodes' ? 'selected' : '' }}>تعداد قسمت‌ها</option>
                                <option value="average_rating" {{ request('sort_by') == 'average_rating' ? 'selected' : '' }}>امتیاز متوسط</option>
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>تاریخ ایجاد</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sort_order" class="form-select">
                                <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>صعودی</option>
                                <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>نزولی</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    @if($people->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>تصویر</th>
                                        <th>نام</th>
                                        <th>نقش‌ها</th>
                                        <th>وضعیت</th>
                                        <th>آمار</th>
                                        <th>تاریخ ایجاد</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($people as $person)
                                    <tr>
                                        <td>
                                            @if($person->image_url)
                                                <img src="{{ $person->image_url }}" alt="{{ $person->name }}" class="rounded-circle" width="40" height="40">
                                            @else
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $person->name }}</strong>
                                                @if($person->bio)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($person->bio, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @foreach($person->roles as $role)
                                                <span class="badge bg-info me-1">
                                                    @switch($role)
                                                        @case('voice_actor') صداپیشه @break
                                                        @case('director') کارگردان @break
                                                        @case('writer') نویسنده @break
                                                        @case('producer') تهیه‌کننده @break
                                                        @case('author') نویسنده اصلی @break
                                                        @case('narrator') گوینده @break
                                                        @default {{ $role }}
                                                    @endswitch
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($person->is_verified)
                                                <span class="badge bg-success">تأیید شده</span>
                                            @else
                                                <span class="badge bg-warning">تأیید نشده</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>
                                                <div>داستان‌ها: {{ $person->total_stories }}</div>
                                                <div>قسمت‌ها: {{ $person->total_episodes }}</div>
                                                @if($person->average_rating > 0)
                                                    <div>امتیاز: {{ number_format($person->average_rating, 1) }}</div>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <small>{{ $person->created_at->format('Y/m/d') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.people.show', $person) }}" class="btn btn-sm btn-outline-info" title="مشاهده">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.people.edit', $person) }}" class="btn btn-sm btn-outline-primary" title="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.people.destroy', $person) }}" method="POST" class="d-inline" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این فرد را حذف کنید؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $people->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">هیچ فردی یافت نشد</h5>
                            <p class="text-muted">برای افزودن فرد جدید، روی دکمه "افزودن فرد جدید" کلیک کنید.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
