{{-- Timeline Error Display Component --}}
@if(session('error') || session('error_details'))
    <div class="timeline-error-alert bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">
                    خطا در ایجاد تایم‌لاین
                </h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>{{ session('error') }}</p>
                    
                    @if(session('error_details'))
                        <div class="mt-3 bg-red-100 rounded p-3">
                            <h4 class="font-semibold text-red-800 mb-2">جزئیات خطا:</h4>
                            <ul class="list-disc list-inside space-y-1">
                                @if(isset(session('error_details')['error_code']))
                                    <li><strong>کد خطا:</strong> {{ session('error_details')['error_code'] }}</li>
                                @endif
                                @if(isset(session('error_details')['timestamp']))
                                    <li><strong>زمان:</strong> {{ session('error_details')['timestamp'] }}</li>
                                @endif
                                @if(isset(session('error_details')['episode_id']))
                                    <li><strong>شناسه اپیزود:</strong> {{ session('error_details')['episode_id'] }}</li>
                                @endif
                                @if(isset(session('error_details')['story_id']))
                                    <li><strong>شناسه داستان:</strong> {{ session('error_details')['story_id'] }}</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
                
                <div class="mt-4">
                    <div class="flex space-x-3">
                        <button onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'" 
                                class="bg-red-100 text-red-800 px-3 py-2 rounded text-sm hover:bg-red-200">
                            بستن
                        </button>
                        <button onclick="window.location.reload()" 
                                class="bg-blue-100 text-blue-800 px-3 py-2 rounded text-sm hover:bg-blue-200">
                            بارگذاری مجدد
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Timeline Success Display Component --}}
@if(session('success'))
    <div class="timeline-success-alert bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-green-800">
                    تایم‌لاین با موفقیت ایجاد شد
                </h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>{{ session('success') }}</p>
                </div>
                
                <div class="mt-4">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'" 
                            class="bg-green-100 text-green-800 px-3 py-2 rounded text-sm hover:bg-green-200">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Timeline Warning Display Component --}}
@if(session('warning'))
    <div class="timeline-warning-alert bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-yellow-800">
                    هشدار
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ session('warning') }}</p>
                </div>
                
                <div class="mt-4">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'" 
                            class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded text-sm hover:bg-yellow-200">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Timeline Validation Errors Display --}}
@if($errors->any())
    <div class="timeline-validation-errors bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">
                    خطاهای اعتبارسنجی
                </h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="mt-4">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.style.display='none'" 
                            class="bg-red-100 text-red-800 px-3 py-2 rounded text-sm hover:bg-red-200">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
