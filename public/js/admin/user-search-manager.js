// User Search Manager - Reusable component for user selection
class UserSearchManager {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = {
            placeholder: 'جستجو کاربر...',
            noResultsText: 'کاربری یافت نشد',
            loadingText: 'در حال جستجو...',
            minSearchLength: 2,
            debounceDelay: 300,
            apiEndpoint: '/admin/users/search',
            ...options
        };
        this.selectedUser = null;
        this.searchResults = [];
        this.isLoading = false;
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        if (!this.container) {
            console.error(`UserSearchManager: Container with ID '${this.containerId}' not found`);
            return;
        }
        
        this.createSearchInterface();
        this.setupEventListeners();
    }

    createSearchInterface() {
        this.container.innerHTML = `
            <div class="user-search-container">
                <div class="relative">
                    <input 
                        type="text" 
                        id="${this.container.id}_search" 
                        class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="${this.options.placeholder}"
                        autocomplete="off"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div id="${this.container.id}_results" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <div class="p-3 text-center text-gray-500">
                        ${this.options.loadingText}
                    </div>
                </div>
                
                <div id="${this.container.id}_selected" class="hidden mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    <span id="${this.container.id}_selected_initials">U</span>
                                </div>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-gray-900" id="${this.container.id}_selected_name">نام کاربر</p>
                                <p class="text-sm text-blue-600 font-medium" id="${this.container.id}_selected_phone">شماره موبایل کاربر</p>
                            </div>
                        </div>
                        <button type="button" id="${this.container.id}_clear" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="user_id" id="${this.container.id}_user_id" value="">
            </div>
        `;
    }

    setupEventListeners() {
        const searchInput = document.getElementById(`${this.container.id}_search`);
        const resultsContainer = document.getElementById(`${this.container.id}_results`);
        const clearButton = document.getElementById(`${this.container.id}_clear`);

        // Search input events
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            // Clear any previous error messages when user starts typing
            if (query.length > 0) {
                this.hideResults();
            }
            this.handleSearch(query);
        });

        searchInput.addEventListener('focus', () => {
            if (this.searchResults.length > 0) {
                resultsContainer.classList.remove('hidden');
            }
        });

        searchInput.addEventListener('blur', () => {
            // Delay hiding to allow clicking on results
            setTimeout(() => {
                resultsContainer.classList.add('hidden');
            }, 200);
        });

        // Clear selection
        clearButton.addEventListener('click', () => {
            this.clearSelection();
        });

        // Click outside to close results
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
    }

    handleSearch(query) {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Hide results and clear any error messages if query is too short
        if (query.length < this.options.minSearchLength) {
            this.hideResults();
            return;
        }

        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.options.debounceDelay);
    }

    async performSearch(query) {
        this.isLoading = true;
        this.showLoading();

        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch(`${this.options.apiEndpoint}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin' // Include cookies for authentication
            });

            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('احراز هویت مورد نیاز است. لطفاً دوباره وارد شوید.');
                } else if (response.status === 404) {
                    throw new Error('سرویس جستجو در دسترس نیست.');
                } else {
                    throw new Error(`خطای سرور: ${response.status}`);
                }
            }

            const data = await response.json();
            this.searchResults = data.users || [];
            this.displayResults();

        } catch (error) {
            console.error('User search error:', error);
            this.showError(error.message || 'خطا در جستجو. لطفاً دوباره تلاش کنید.');
        } finally {
            this.isLoading = false;
        }
    }

    displayResults() {
        const resultsContainer = document.getElementById(`${this.container.id}_results`);
        
        if (this.searchResults.length === 0) {
            resultsContainer.innerHTML = `
                <div class="p-3 text-center text-gray-500">
                    ${this.options.noResultsText}
                </div>
            `;
        } else {
            resultsContainer.innerHTML = this.searchResults.map(user => `
                <div class="user-result-item p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" data-user-id="${user.id}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 bg-gray-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                ${this.getInitials(user.first_name, user.last_name)}
                            </div>
                        </div>
                        <div class="mr-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                ${user.first_name} ${user.last_name}
                            </p>
                            <p class="text-sm text-blue-600 font-medium">${user.phone_number}</p>
                            ${user.email ? `<p class="text-xs text-gray-400">${user.email}</p>` : ''}
                        </div>
                        <div class="text-xs text-gray-400">
                            ID: ${user.id}
                        </div>
                    </div>
                </div>
            `).join('');

            // Add click listeners to result items
            resultsContainer.querySelectorAll('.user-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const userId = item.dataset.userId;
                    const user = this.searchResults.find(u => u.id == userId);
                    this.selectUser(user);
                });
            });
        }

        resultsContainer.classList.remove('hidden');
    }

    selectUser(user) {
        this.selectedUser = user;
        
        // Update hidden input
        document.getElementById(`${this.container.id}_user_id`).value = user.id;
        
        // Update selected user display
        document.getElementById(`${this.container.id}_selected_name`).textContent = 
            `${user.first_name} ${user.last_name}`;
        document.getElementById(`${this.container.id}_selected_phone`).textContent = user.phone_number;
        document.getElementById(`${this.container.id}_selected_initials`).textContent = 
            this.getInitials(user.first_name, user.last_name);
        
        // Show selected user section
        document.getElementById(`${this.container.id}_selected`).classList.remove('hidden');
        
        // Clear search input and hide results
        document.getElementById(`${this.container.id}_search`).value = '';
        document.getElementById(`${this.container.id}_results`).classList.add('hidden');
        
        // Trigger custom event
        this.container.dispatchEvent(new CustomEvent('userSelected', {
            detail: { user: user }
        }));
    }

    clearSelection() {
        this.selectedUser = null;
        
        // Clear hidden input
        document.getElementById(`${this.container.id}_user_id`).value = '';
        
        // Hide selected user section
        document.getElementById(`${this.container.id}_selected`).classList.add('hidden');
        
        // Clear search input
        document.getElementById(`${this.container.id}_search`).value = '';
        
        // Trigger custom event
        this.container.dispatchEvent(new CustomEvent('userCleared', {
            detail: {}
        }));
    }

    showLoading() {
        const resultsContainer = document.getElementById(`${this.container.id}_results`);
        resultsContainer.innerHTML = `
            <div class="p-3 text-center text-gray-500">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    ${this.options.loadingText}
                </div>
            </div>
        `;
        resultsContainer.classList.remove('hidden');
    }

    showError(message) {
        const resultsContainer = document.getElementById(`${this.container.id}_results`);
        resultsContainer.innerHTML = `
            <div class="p-3 text-center text-red-600 bg-red-50 border-t border-red-200">
                <div class="flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ${message}
                </div>
            </div>
        `;
        resultsContainer.classList.remove('hidden');
    }

    hideResults() {
        document.getElementById(`${this.container.id}_results`).classList.add('hidden');
    }

    getInitials(firstName, lastName) {
        const first = firstName ? firstName.charAt(0).toUpperCase() : '';
        const last = lastName ? lastName.charAt(0).toUpperCase() : '';
        return first + last;
    }

    // Public methods
    getSelectedUser() {
        return this.selectedUser;
    }

    setSelectedUser(user) {
        this.selectUser(user);
    }

    clear() {
        this.clearSelection();
    }
}

// Initialize user search managers when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize all user search containers
    document.querySelectorAll('[data-user-search]').forEach(container => {
        const options = JSON.parse(container.dataset.userSearch || '{}');
        new UserSearchManager(container.id, options);
    });
});
