{{-- Standardized Data Table Component --}}
<div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
    {{-- Table Header with Bulk Actions --}}
    @if(isset($bulkActions) && $bulkActions)
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="select-all" class="text-sm font-medium text-gray-700">انتخاب همه</label>
            </div>
            
            <div class="flex items-center space-x-3 space-x-reverse">
                <select id="bulk-action" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">عملیات گروهی</option>
                    @foreach($bulkActionOptions ?? [] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="executeBulkAction()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    اجرا
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @if(isset($bulkActions) && $bulkActions)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </th>
                    @endif
                    
                    @foreach($columns as $column)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $column['title'] }}
                        @if(isset($column['sortable']) && $column['sortable'])
                        <button onclick="sortTable('{{ $column['key'] }}')" class="mr-1 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </button>
                        @endif
                    </th>
                    @endforeach
                    
                    @if(isset($actions) && $actions)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        عملیات
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($data as $index => $item)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    @if(isset($bulkActions) && $bulkActions)
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" name="selected_items[]" value="{{ $item['id'] ?? $index }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </td>
                    @endif
                    
                    @foreach($columns as $column)
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(isset($column['render']))
                            {!! $column['render']($item) !!}
                        @else
                            {{ $item[$column['key']] ?? '-' }}
                        @endif
                    </td>
                    @endforeach
                    
                    @if(isset($actions) && $actions)
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            @foreach($actions as $action)
                                @if(isset($action['condition']) && !$action['condition']($item))
                                    @continue
                                @endif
                                
                                @if($action['type'] === 'link')
                                <a href="{{ $action['url']($item) }}" class="{{ $action['class'] ?? 'text-blue-600 hover:text-blue-900' }}">
                                    {!! $action['icon'] ?? '' !!}
                                    {{ $action['label'] }}
                                </a>
                                @elseif($action['type'] === 'button')
                                <button onclick="{{ $action['onclick']($item) }}" class="{{ $action['class'] ?? 'text-red-600 hover:text-red-900' }}">
                                    {!! $action['icon'] ?? '' !!}
                                    {{ $action['label'] }}
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($columns) + (isset($bulkActions) && $bulkActions ? 1 : 0) + (isset($actions) && $actions ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 mb-2">هیچ داده‌ای یافت نشد</p>
                            <p class="text-sm text-gray-500">لطفاً فیلترهای جستجو را تغییر دهید یا داده جدیدی اضافه کنید.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(isset($pagination) && $pagination)
    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center text-sm text-gray-700">
                نمایش {{ $pagination['from'] ?? 0 }} تا {{ $pagination['to'] ?? 0 }} از {{ $pagination['total'] ?? 0 }} نتیجه
            </div>
            
            <div class="flex items-center space-x-2 space-x-reverse">
                @if(isset($pagination['links']))
                    @foreach($pagination['links'] as $link)
                        @if($link['url'])
                        <a href="{{ $link['url'] }}" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 {{ $link['active'] ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700' }}">
                            {!! $link['label'] !!}
                        </a>
                        @else
                        <span class="px-3 py-1 text-sm border border-gray-300 rounded-lg text-gray-400">
                            {!! $link['label'] !!}
                        </span>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function sortTable(column) {
    const url = new URL(window.location);
    const currentSort = url.searchParams.get('sort');
    const currentDirection = url.searchParams.get('direction');
    
    let direction = 'asc';
    if (currentSort === column && currentDirection === 'asc') {
        direction = 'desc';
    }
    
    url.searchParams.set('sort', column);
    url.searchParams.set('direction', direction);
    window.location.href = url.toString();
}

function executeBulkAction() {
    const selectedItems = document.querySelectorAll('input[name="selected_items[]"]:checked');
    const action = document.getElementById('bulk-action').value;
    
    if (selectedItems.length === 0) {
        alert('لطفاً حداقل یک مورد را انتخاب کنید.');
        return;
    }
    
    if (!action) {
        alert('لطفاً یک عملیات را انتخاب کنید.');
        return;
    }
    
    if (confirm(`آیا از اجرای عملیات "${action}" روی ${selectedItems.length} مورد انتخاب شده اطمینان دارید؟`)) {
        // Implementation for bulk action
        console.log('Executing bulk action:', action, 'on', selectedItems.length, 'items');
    }
}

function exportData() {
    // Implementation for data export
    console.log('Exporting data...');
}
</script>
