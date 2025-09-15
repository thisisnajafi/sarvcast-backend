@extends('admin.layouts.app')

@section('title', 'افزودن فرد جدید')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">افزودن فرد جدید</h3>
                </div>

                <form action="{{ route('admin.people.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">نام <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image" class="form-label">تصویر</label>
                                    <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                           id="image" name="image" accept="image/*">
                                    @error('image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">فرمت‌های مجاز: JPEG, PNG, JPG, WebP - حداکثر 2 مگابایت</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">بیوگرافی</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" 
                                      id="bio" name="bio" rows="4">{{ old('bio') }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">نقش‌ها <span class="text-danger">*</span></label>
                                    <div class="row">
                                        @foreach($roles as $role)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="roles[]" value="{{ $role }}" 
                                                       id="role_{{ $role }}"
                                                       {{ in_array($role, old('roles', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="role_{{ $role }}">
                                                    @switch($role)
                                                        @case('voice_actor') صداپیشه @break
                                                        @case('director') کارگردان @break
                                                        @case('writer') نویسنده @break
                                                        @case('producer') تهیه‌کننده @break
                                                        @case('author') نویسنده اصلی @break
                                                        @case('narrator') گوینده @break
                                                        @default {{ $role }}
                                                    @endswitch
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @error('roles')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="is_verified" value="1" 
                                               id="is_verified"
                                               {{ old('is_verified') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_verified">
                                            تأیید شده
                                        </label>
                                    </div>
                                    <div class="form-text">افراد تأیید شده در نتایج جستجو اولویت دارند</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.people.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i>
                                بازگشت
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                ذخیره
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
