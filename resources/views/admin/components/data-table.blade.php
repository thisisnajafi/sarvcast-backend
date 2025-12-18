{{-- Standardized Data Table Component --}}
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors duration-300">
    {{-- Table Header with Bulk Actions --}}
    @if(isset($bulkActions) && $bulkActions)
    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                <label for="select-all" class="text-sm font-medium text-gray-700 dark:text-gray-300">انتخاب همه</label>
            </div>
            
            <div class="flex items-center space-x-3 space-x-reverse">
                <select id="bulk-action" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
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
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @if(isset($bulkActions) && $bulkActions)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                    </th>
                    @endif
                    
                    @foreach($columns as $column)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        {{ $column['title'] }}
                        @if(isset($column['sortable']) && $column['sortable'])
                        <button onclick="sortTable('{{ $column['key'] }}')" class="mr-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </button>
                        @endif
                    </th>
                    @endforeach
                    
                    @if(isset($actions) && $actions)
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        عملیات
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($data as $index => $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    @if(isset($bulkActions) && $bulkActions)
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" name="selected_items[]" value="{{ $item['id'] ?? $index }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                    </td>
                    @endif
                    
                    @foreach($columns as $column)
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
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
                                <a href="{{ $action['url']($item) }}" class="{{ $action['class'] ?? 'text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300' }}">
                                    {!! $action['icon'] ?? '' !!}
                                    {{ $action['label'] }}
                                </a>
                                @elseif($action['type'] === 'button')
                                <button onclick="{{ $action['onclick']($item) }}" class="{{ $action['class'] ?? 'text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300' }}">
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
                    <td colspan="{{ count($columns) + (isset($bulkActions) && $bulkActions ? 1 : 0) + (isset($actions) && $actions ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">هیچ داده‌ای یافت نشد</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">لطفاً فیلترهای جستجو را تغییر دهید یا داده جدیدی اضافه کنید.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(isset($pagination) && $pagination)
    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
        <div class="flex items-center justify-between">
            <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                @if(is_array($pagination))
                    نمایش {{ $pagination['from'] ?? 0 }} تا {{ $pagination['to'] ?? 0 }} از {{ $pagination['total'] ?? 0 }} نتیجه
                @elseif(is_object($pagination) && method_exists($pagination, 'firstItem'))
                    نمایش {{ $pagination->firstItem() ?? 0 }} تا {{ $pagination->lastItem() ?? 0 }} از {{ $pagination->total() ?? 0 }} نتیجه
                @endif
            </div>

            <div class="flex items-center space-x-2 space-x-reverse">
                @if(is_array($pagination) && isset($pagination['links']))
                    @foreach($pagination['links'] as $link)
                        @if($link['url'])
                        <a href="{{ $link['url'] }}" class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 {{ $link['active'] ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 dark:text-gray-300' }}">
                            {!! $link['label'] !!}
                        </a>
                        @else
                        <span class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-400 dark:text-gray-500">
                            {!! $link['label'] !!}
                        </span>
                        @endif
                    @endforeach
                @elseif(is_object($pagination) && method_exists($pagination, 'links'))
                    {!! $pagination->links() !!}
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
