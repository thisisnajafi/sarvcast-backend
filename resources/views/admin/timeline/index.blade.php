@extends('admin.layouts.app')

@section('title', 'مدیریت تایم‌لاین تصاویر')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-images"></i>
                        مدیریت تایم‌لاین تصاویر - {{ $episode->title }}
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#timelineModal">
                            <i class="fas fa-plus"></i> افزودن تایم‌لاین
                        </button>
                        <button type="button" class="btn btn-info" onclick="validateTimeline()">
                            <i class="fas fa-check"></i> اعتبارسنجی
                        </button>
                        <button type="button" class="btn btn-warning" onclick="optimizeTimeline()">
                            <i class="fas fa-magic"></i> بهینه‌سازی
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Episode Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-play"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">مدت زمان اپیزود</span>
                                    <span class="info-box-number" id="episodeDuration">{{ $episode->duration }} ثانیه</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-images"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تعداد تصاویر</span>
                                    <span class="info-box-number" id="timelineCount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Status -->
                    <div class="alert alert-info" id="timelineStatus">
                        <i class="fas fa-info-circle"></i>
                        <span id="statusText">در حال بارگذاری...</span>
                    </div>

                    <!-- Timeline Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="timelineTable">
                            <thead>
                                <tr>
                                    <th>ترتیب</th>
                                    <th>زمان شروع (ثانیه)</th>
                                    <th>زمان پایان (ثانیه)</th>
                                    <th>مدت زمان</th>
                                    <th>تصویر</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody id="timelineBody">
                                <!-- Timeline entries will be loaded here -->
                            </tbody>
                        </table>
    </div>

                    <!-- Statistics -->
                    <div class="row mt-4" id="statisticsSection" style="display: none;">
                        <div class="col-12">
                            <h5>آمار تایم‌لاین</h5>
                            <div class="row" id="statisticsContent">
                                <!-- Statistics will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timeline Modal -->
<div class="modal fade" id="timelineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن/ویرایش تایم‌لاین</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="timelineForm">
                    <div class="form-group">
                        <label>مدت زمان اپیزود (ثانیه)</label>
                        <input type="number" class="form-control" id="episodeDurationInput" 
                               value="{{ $episode->duration }}" readonly>
                    </div>
                    
                    <div id="timelineEntries">
                        <!-- Timeline entries will be added here -->
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="addTimelineEntry()">
                        <i class="fas fa-plus"></i> افزودن ورودی
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="saveTimeline()">ذخیره</button>
            </div>
        </div>
    </div>
    </div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">پیش‌نمایش تصویر</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="پیش‌نمایش" class="img-fluid">
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let timelineData = [];
let episodeId = '{{ $episode->id }}';
let episodeDuration = {{ $episode->duration }};

$(document).ready(function() {
    loadTimeline();
    loadStatistics();
});

function loadTimeline() {
    $.ajax({
        url: `/api/v1/admin/episodes/${episodeId}/timeline`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + getAuthToken(),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                timelineData = response.data.timeline;
                displayTimeline(timelineData);
                updateStatus('تایم‌لاین بارگذاری شد', 'success');
            }
        },
        error: function(xhr) {
            updateStatus('خطا در بارگذاری تایم‌لاین', 'danger');
        }
    });
}

function displayTimeline(timeline) {
    const tbody = $('#timelineBody');
    tbody.empty();
    
    if (timeline.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center text-muted">
                    هیچ تایم‌لاینی تعریف نشده است
                </td>
            </tr>
        `);
        $('#timelineCount').text('0');
        return;
    }
    
    timeline.forEach((entry, index) => {
        const duration = entry.end_time - entry.start_time;
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${entry.start_time}</td>
                <td>${entry.end_time}</td>
                <td>${duration} ثانیه</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="previewImage('${entry.image_url}')">
                        <i class="fas fa-eye"></i> پیش‌نمایش
                    </button>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteTimelineEntry(${index})">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </td>
            </tr>
        `);
    });
    
    $('#timelineCount').text(timeline.length);
}

function addTimelineEntry() {
    const container = $('#timelineEntries');
    const index = container.children().length;
    
    container.append(`
        <div class="timeline-entry border p-3 mb-3" data-index="${index}">
            <div class="row">
                <div class="col-md-4">
                    <label>زمان شروع (ثانیه)</label>
                    <input type="number" class="form-control start-time" min="0" max="${episodeDuration}" required>
                </div>
                <div class="col-md-4">
                    <label>زمان پایان (ثانیه)</label>
                    <input type="number" class="form-control end-time" min="0" max="${episodeDuration}" required>
                </div>
                <div class="col-md-4">
                    <label>URL تصویر</label>
                    <input type="url" class="form-control image-url" required>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeTimelineEntry(${index})">
                        <i class="fas fa-trash"></i> حذف این ورودی
                    </button>
                </div>
            </div>
        </div>
    `);
}

function removeTimelineEntry(index) {
    $(`.timeline-entry[data-index="${index}"]`).remove();
}

function saveTimeline() {
    const entries = [];
    let isValid = true;
    
    $('.timeline-entry').each(function() {
        const startTime = parseInt($(this).find('.start-time').val());
        const endTime = parseInt($(this).find('.end-time').val());
        const imageUrl = $(this).find('.image-url').val();
        
        if (!startTime || !endTime || !imageUrl) {
            isValid = false;
            return false;
        }
        
        if (startTime >= endTime) {
            isValid = false;
            alert('زمان شروع باید کمتر از زمان پایان باشد');
            return false;
        }
        
        entries.push({
            start_time: startTime,
            end_time: endTime,
            image_url: imageUrl
        });
    });
    
    if (!isValid) {
        alert('لطفاً تمام فیلدها را پر کنید');
        return;
    }
    
    $.ajax({
        url: `/api/v1/admin/episodes/${episodeId}/timeline`,
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + getAuthToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            image_timeline: entries
        }),
        success: function(response) {
            if (response.success) {
                $('#timelineModal').modal('hide');
                loadTimeline();
                loadStatistics();
                updateStatus('تایم‌لاین با موفقیت ذخیره شد', 'success');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            updateStatus('خطا در ذخیره تایم‌لاین: ' + (error?.message || 'خطای نامشخص'), 'danger');
        }
    });
}

function validateTimeline() {
    if (timelineData.length === 0) {
        updateStatus('هیچ تایم‌لاینی برای اعتبارسنجی وجود ندارد', 'warning');
        return;
    }
    
    $.ajax({
        url: '/api/v1/admin/timeline/validate',
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + getAuthToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            episode_duration: episodeDuration,
            image_timeline: timelineData
        }),
        success: function(response) {
            if (response.success) {
                updateStatus('تایم‌لاین معتبر است', 'success');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            updateStatus('خطا در اعتبارسنجی: ' + (error?.message || 'خطای نامشخص'), 'danger');
        }
    });
}

function optimizeTimeline() {
    if (timelineData.length === 0) {
        updateStatus('هیچ تایم‌لاینی برای بهینه‌سازی وجود ندارد', 'warning');
        return;
    }
    
    $.ajax({
        url: '/api/v1/admin/timeline/optimize',
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + getAuthToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            image_timeline: timelineData
        }),
        success: function(response) {
            if (response.success) {
                const data = response.data;
                updateStatus(`تایم‌لاین بهینه شد: ${data.original_count} → ${data.optimized_count} ورودی`, 'success');
                
                if (confirm('آیا می‌خواهید تایم‌لاین بهینه شده را ذخیره کنید؟')) {
                    timelineData = data.optimized_timeline;
                    saveTimeline();
                }
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            updateStatus('خطا در بهینه‌سازی: ' + (error?.message || 'خطای نامشخص'), 'danger');
        }
    });
}

function previewImage(imageUrl) {
    $('#previewImage').attr('src', imageUrl);
    $('#imagePreviewModal').modal('show');
}

function deleteTimelineEntry(index) {
    if (confirm('آیا مطمئن هستید که می‌خواهید این ورودی را حذف کنید؟')) {
        timelineData.splice(index, 1);
        displayTimeline(timelineData);
    }
}

function loadStatistics() {
    $.ajax({
        url: `/api/v1/admin/episodes/${episodeId}/timeline/statistics`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + getAuthToken(),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                displayStatistics(response.data.statistics);
            }
        }
    });
}

function displayStatistics(stats) {
    const container = $('#statisticsContent');
    container.empty();
    
    container.append(`
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary">
                    <i class="fas fa-list"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">کل ورودی‌ها</span>
                    <span class="info-box-number">${stats.total_entries}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success">
                    <i class="fas fa-play"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">شروع اولین تصویر</span>
                    <span class="info-box-number">${stats.first_image_start}s</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning">
                    <i class="fas fa-stop"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">پایان آخرین تصویر</span>
                    <span class="info-box-number">${stats.last_image_end}s</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info">
                    <i class="fas fa-images"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">تصاویر منحصر به فرد</span>
                    <span class="info-box-number">${stats.unique_images}</span>
                </div>
            </div>
        </div>
    `);
    
    $('#statisticsSection').show();
}

function updateStatus(message, type) {
    const statusAlert = $('#timelineStatus');
    statusAlert.removeClass('alert-info alert-success alert-warning alert-danger');
    statusAlert.addClass(`alert-${type}`);
    $('#statusText').text(message);
}

function getAuthToken() {
    return localStorage.getItem('auth_token') || '{{ auth()->user()->createToken("admin")->plainTextToken }}';
}
</script>
@endsection