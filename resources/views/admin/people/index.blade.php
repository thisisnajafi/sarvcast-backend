@extends('admin.layouts.app')

@section('title', 'مدیریت افراد')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت افراد</h1>
        <a href="{{ route('admin.people.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
            افزودن فرد جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="جستجو در نام یا بیوگرافی..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">نقش</label>
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه نقش‌ها</option>
                    <option value="voice_actor" {{ request('role') == 'voice_actor' ? 'selected' : '' }}>صداپیشه</option>
                    <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>کارگردان</option>
                    <option value="writer" {{ request('role') == 'writer' ? 'selected' : '' }}>نویسنده</option>
                    <option value="producer" {{ request('role') == 'producer' ? 'selected' : '' }}>تهیه‌کننده</option>
                    <option value="author" {{ request('role') == 'author' ? 'selected' : '' }}>نویسنده اصلی</option>
                    <option value="narrator" {{ request('role') == 'narrator' ? 'selected' : '' }}>گوینده</option>
                </select>
            </div>
            
            <div class="min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت تأیید</label>
                <select name="verified" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>تأیید شده</option>
                    <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>تأیید نشده</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
                    فیلتر
                </button>
                <a href="{{ route('admin.people.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition duration-200 mr-2">
                    پاک کردن
                </a>
            </div>
        </form>
    </div>

    <!-- People Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تصویر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نقش‌ها</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">آمار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($people as $person)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($person->image_url)
                                    <img src="{{ $person->image_url }}" alt="{{ $person->name }}" class="w-12 h-12 object-cover rounded-lg">
                                @else
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $person->name }}</div>
                                @if($person->bio)
                                    <div class="text-sm text-gray-500 max-w-xs truncate">{{ Str::limit($person->bio, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($person->roles as $role)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $person->is_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $person->is_verified ? 'تأیید شده' : 'تأیید نشده' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>داستان‌ها: {{ $person->total_stories }}</div>
                                <div>قسمت‌ها: {{ $person->total_episodes }}</div>
                                @if($person->average_rating > 0)
                                    <div>امتیاز: {{ number_format($person->average_rating, 1) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @jalali($person->created_at, 'Y/m/d')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse">
                                    <a href="{{ route('admin.people.show', $person) }}" 
                                       class="text-primary hover:text-blue-600">مشاهده</a>
                                    <a href="{{ route('admin.people.edit', $person) }}" 
                                       class="text-green-600 hover:text-green-800">ویرایش</a>
                                    <form method="POST" action="{{ route('admin.people.destroy', $person) }}" class="inline" 
                                          onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این فرد را حذف کنید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <div class="py-8">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <h5 class="text-gray-500 mb-2">هیچ فردی یافت نشد</h5>
                                    <p class="text-gray-400">برای افزودن فرد جدید، روی دکمه "افزودن فرد جدید" کلیک کنید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($people->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $people->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
