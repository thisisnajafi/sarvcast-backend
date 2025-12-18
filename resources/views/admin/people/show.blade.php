@extends('admin.layouts.app')

@section('title', 'مشاهده فرد: ' . $person->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">مشاهده فرد: {{ $person->name }}</h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.people.edit', $person) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i>
                            ویرایش
                        </a>
                        <a href="{{ route('admin.people.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i>
                            بازگشت
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                @if($person->image_url)
                                    <img src="{{ $person->image_url }}" alt="{{ $person->name }}" class="img-fluid rounded-circle mb-3" style="max-width: 200px;">
                                @else
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 200px; height: 200px;">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                @endif
                                
                                <h4>{{ $person->name }}</h4>
                                
                                @if($person->is_verified)
                                    <span class="badge bg-success fs-6">تأیید شده</span>
                                @else
                                    <span class="badge bg-warning fs-6">تأیید نشده</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>نقش‌ها</h5>
                                    <div class="mb-3">
                                        @foreach($person->roles as $role)
                                            <span class="badge bg-info me-2 mb-2 fs-6">
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
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5>آمار</h5>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <h3 class="text-primary mb-1">{{ $person->total_stories }}</h3>
                                                <small class="text-muted">داستان‌ها</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <h3 class="text-success mb-1">{{ $person->total_episodes }}</h3>
                                                <small class="text-muted">قسمت‌ها</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <h3 class="text-warning mb-1">{{ number_format($person->average_rating, 1) }}</h3>
                                                <small class="text-muted">امتیاز متوسط</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <h3 class="text-info mb-1">{{ $person->created_at->format('Y/m/d') }}</h3>
                                                <small class="text-muted">تاریخ ایجاد</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($person->bio)
                                <div class="mt-4">
                                    <h5>بیوگرافی</h5>
                                    <p class="text-muted">{{ $person->bio }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stories Section -->
            @if($person->stories->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">داستان‌های این فرد</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>تصویر</th>
                                        <th>عنوان</th>
                                        <th>دسته‌بندی</th>
                                        <th>وضعیت</th>
                                        <th>تعداد قسمت‌ها</th>
                                        <th>امتیاز</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($person->stories as $story)
                                    <tr>
                                        <td>
                                            @if($story->image_url)
                                                <img src="{{ $story->image_url }}" alt="{{ $story->title }}" class="rounded" width="40" height="40">
                                            @else
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-book text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $story->title }}</strong>
                                                @if($story->subtitle)
                                                    <br>
                                                    <small class="text-muted">{{ $story->subtitle }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $story->category->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $story->status === 'published' ? 'success' : 'warning' }}">
                                                {{ ucfirst($story->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $story->total_episodes }}</td>
                                        <td>
                                            @if($story->rating > 0)
                                                <div class="d-flex align-items-center">
                                                    <span class="text-warning me-1">★</span>
                                                    <span>{{ number_format($story->rating, 1) }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Episodes Section -->
            @if($person->episodes->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">قسمت‌های این فرد</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>عنوان</th>
                                        <th>داستان</th>
                                        <th>شماره قسمت</th>
                                        <th>مدت زمان</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($person->episodes as $episode)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $episode->title }}</strong>
                                                @if($episode->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($episode->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $episode->story->title }}</td>
                                        <td>{{ $episode->episode_number }}</td>
                                        <td>{{ $episode->duration }} دقیقه</td>
                                        <td>
                                            <span class="badge bg-{{ $episode->status === 'published' ? 'success' : 'warning' }}">
                                                {{ ucfirst($episode->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.episodes.show', $episode) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
