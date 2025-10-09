@extends('admin.layouts.app')

@section('title', 'مدیریت تایم‌لاین جدید')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-images mr-2"></i>
                        مدیریت تایم‌لاین جدید
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.episodes.timeline.index', $episode) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right mr-1"></i>
                            بازگشت به لیست تایم‌لاین‌ها
        </a>
    </div>
                    </div>
                    
                <div class="card-body">
                    {{-- Include Timeline Alerts Component --}}
                    <x-timeline-alerts />

                    {{-- Episode Information --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-play"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">اپیزود</span>
                                    <span class="info-box-number">{{ $episode->title }}</span>
                    </div>
                </div>
            </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">مدت زمان</span>
                                    <span class="info-box-number">{{ $episode->duration }} ثانیه</span>
            </div>
            </div>
        </div>
    </div>

                    {{-- Timeline Creation Form --}}
                    <form id="timeline-creation-form" 
                          class="timeline-creation-form timeline-form" 
                          action="{{ route('admin.episodes.timeline.store', $episode) }}" 
                          method="POST" 
                          enctype="multipart/form-data">
            @csrf

                        {{-- Hidden Episode Duration --}}
                        <input type="hidden" id="episode-duration" value="{{ $episode->duration }}">

                        {{-- Timeline Entries Container --}}
                        <div class="timeline-entries-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-list mr-2"></i>
                                    ورودی‌های تایم‌لاین
                                </h5>
                                <button type="button" id="add-timeline-entry" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i>
                                    افزودن ورودی جدید
                                </button>
                            </div>

                            <div id="timeline-entries-container">
                                {{-- Timeline entries will be added here dynamically --}}
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="form-actions mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-lg create-timeline-btn">
                                        <i class="fas fa-save mr-2"></i>
                                        ایجاد تایم‌لاین
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="timelineCreationForm.clearForm()">
                                        <i class="fas fa-undo mr-2"></i>
                                        پاک کردن فرم
                                    </button>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="{{ route('admin.episodes.timeline.index', $episode) }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times mr-2"></i>
                    انصراف
                </a>
                                </div>
                            </div>
            </div>
        </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Timeline Entry Template --}}
<template id="timeline-entry-template">
    <div class="timeline-entry card mb-3" data-timeline-index="INDEX">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-image mr-2"></i>
                    ورودی تایم‌لاین <span class="entry-number">INDEX</span>
                </h6>
                <button type="button" class="btn btn-sm btn-danger remove-timeline-entry" data-index="INDEX">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Start Time --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="timeline_INDEX_start_time">
                            <i class="fas fa-play mr-1"></i>
                            زمان شروع (ثانیه)
                        </label>
                        <input type="number" 
                               id="timeline_INDEX_start_time" 
                               name="timeline[INDEX][start_time]" 
                               class="form-control" 
                               min="0" 
                               max="{{ $episode->duration }}" 
                               required>
                        <small class="form-text text-muted">زمان شروع این بخش از اپیزود</small>
                </div>
            </div>

                {{-- End Time --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="timeline_INDEX_end_time">
                            <i class="fas fa-stop mr-1"></i>
                            زمان پایان (ثانیه)
                        </label>
                        <input type="number" 
                               id="timeline_INDEX_end_time" 
                               name="timeline[INDEX][end_time]" 
                               class="form-control" 
                               min="0" 
                               max="{{ $episode->duration }}" 
                               required>
                        <small class="form-text text-muted">زمان پایان این بخش از اپیزود</small>
            </div>
        </div>

                {{-- Image Order --}}
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="timeline_INDEX_image_order">
                            <i class="fas fa-sort-numeric-up mr-1"></i>
                            ترتیب
                        </label>
                        <input type="number" 
                               id="timeline_INDEX_image_order" 
                               name="timeline[INDEX][image_order]" 
                               class="form-control" 
                               min="1" 
                               value="1" 
                               required>
            </div>
            </div>

                {{-- Transition Type --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="timeline_INDEX_transition_type">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            نوع انتقال
                        </label>
                        <select id="timeline_INDEX_transition_type" 
                                name="timeline[INDEX][transition_type]" 
                                class="form-control" 
                                required>
                            <option value="fade">محو شدن (Fade)</option>
                            <option value="slide">لغزش (Slide)</option>
                            <option value="cut">برش (Cut)</option>
                            <option value="dissolve">حل شدن (Dissolve)</option>
                </select>
            </div>
                </div>
            </div>

            <div class="row">
                {{-- Image URL --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="timeline_INDEX_image_url">
                            <i class="fas fa-link mr-1"></i>
                            آدرس تصویر
                        </label>
                        <input type="url" 
                               id="timeline_INDEX_image_url" 
                               name="timeline[INDEX][image_url]" 
                               class="form-control" 
                               placeholder="https://example.com/image.jpg" 
                               required>
                        <small class="form-text text-muted">آدرس کامل تصویر</small>
                    </div>
                </div>

                {{-- Key Frame --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="form-check mt-4">
                            <input type="checkbox" 
                                   id="timeline_INDEX_is_key_frame" 
                                   name="timeline[INDEX][is_key_frame]" 
                                   class="form-check-input" 
                                   value="1">
                            <label class="form-check-label" for="timeline_INDEX_is_key_frame">
                                <i class="fas fa-star mr-1"></i>
                                فریم کلیدی
                </label>
            </div>
        </div>
                </div>
        </div>

            {{-- Scene Description --}}
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="timeline_INDEX_scene_description">
                            <i class="fas fa-align-right mr-1"></i>
                            توضیح صحنه
                        </label>
                        <textarea id="timeline_INDEX_scene_description" 
                                  name="timeline[INDEX][scene_description]" 
                                  class="form-control" 
                                  rows="2" 
                                  placeholder="توضیح کوتاه در مورد این بخش از داستان..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Image Preview --}}
            <div class="row">
                <div class="col-12">
                    <div class="image-preview-container">
                        <div class="image-preview bg-light p-3 rounded text-center">
                            <i class="fas fa-image text-muted fa-3x"></i>
                            <p class="text-muted mt-2">پیش‌نمایش تصویر</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- Existing Timeline Data (if any) --}}
@if(session('timeline_data'))
    <div id="existing-timeline-data" style="display: none;">
        {{ json_encode(session('timeline_data')) }}
    </div>
@endif
@endsection

@section('scripts')
{{-- Include Timeline Error Handler --}}
<script src="{{ asset('js/timeline-error-handler.js') }}"></script>

{{-- Include Timeline Creation Form Handler --}}
<script src="{{ asset('js/timeline-creation-form.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize timeline creation form
    window.timelineCreationForm = new TimelineCreationForm();
    
    // Add first timeline entry by default
    if (document.getElementById('timeline-entries-container').children.length === 0) {
        document.getElementById('add-timeline-entry').click();
    }
});
</script>
@endsection

@section('styles')
<style>
.timeline-entry {
    border-left: 4px solid #007bff;
}

.timeline-entry .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.timeline-entry .entry-number {
    background-color: #007bff;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.image-preview-container {
    margin-top: 15px;
}

.image-preview img {
    max-width: 200px;
    max-height: 150px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-error-alert,
.timeline-success-alert,
.timeline-warning-alert {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.timeline-field-error {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loading-spinner {
    display: inline-block;
    width: 1em;
    height: 1em;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection
