/**
 * Admin JavaScript Base Template
 * Provides common functionality for admin pages
 */

class AdminManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
    }

    setupEventListeners() {
        // Common event listeners for admin pages
        document.addEventListener('DOMContentLoaded', () => {
            this.handleFormSubmissions();
            this.handleBulkActions();
            this.handleSearch();
            this.handlePagination();
        });
    }

    initializeComponents() {
        // Initialize common admin components
        this.initializeTooltips();
        this.initializeModals();
        this.initializeDataTables();
    }

    handleFormSubmissions() {
        const forms = document.querySelectorAll('form[data-ajax]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitFormAjax(form);
            });
        });
    }

    handleBulkActions() {
        const bulkActionForm = document.getElementById('bulk-action-form');
        if (bulkActionForm) {
            bulkActionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleBulkAction(bulkActionForm);
            });
        }
    }

    handleSearch() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.performSearch(e.target.value);
            }, 300));
        }
    }

    handlePagination() {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadPage(link.href);
            });
        });
    }

    submitFormAjax(form) {
        const formData = new FormData(form);
        const url = form.action;
        const method = form.method || 'POST';

        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.refreshPage();
                }
            } else {
                this.showNotification(data.message, 'error');
                if (data.errors) {
                    this.displayValidationErrors(data.errors);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('خطا در ارسال درخواست', 'error');
        });
    }

    handleBulkAction(form) {
        const selectedItems = document.querySelectorAll('input[name="selected_items[]"]:checked');
        if (selectedItems.length === 0) {
            this.showNotification('لطفاً حداقل یک آیتم را انتخاب کنید', 'warning');
            return;
        }

        const action = form.querySelector('select[name="action"]').value;
        const confirmMessage = this.getBulkActionConfirmMessage(action);
        
        if (confirm(confirmMessage)) {
            this.submitFormAjax(form);
        }
    }

    performSearch(query) {
        const url = new URL(window.location);
        url.searchParams.set('search', query);
        url.searchParams.delete('page'); // Reset to first page
        
        this.loadPage(url.toString());
    }

    loadPage(url) {
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Update the main content area
            const contentArea = document.querySelector('.content-area') || document.querySelector('main');
            if (contentArea) {
                contentArea.innerHTML = html;
                this.initializeComponents(); // Re-initialize components
            }
        })
        .catch(error => {
            console.error('Error loading page:', error);
            this.showNotification('خطا در بارگذاری صفحه', 'error');
        });
    }

    initializeTooltips() {
        // Initialize tooltips if using a tooltip library
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]');
        }
    }

    initializeModals() {
        // Initialize modals
        const modalTriggers = document.querySelectorAll('[data-modal-target]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.getAttribute('data-modal-target');
                this.openModal(modalId);
            });
        });

        const modalCloses = document.querySelectorAll('[data-modal-close]');
        modalCloses.forEach(close => {
            close.addEventListener('click', () => {
                this.closeModal(close.closest('.modal'));
            });
        });
    }

    initializeDataTables() {
        // Initialize data tables if present
        const tables = document.querySelectorAll('table[data-sortable]');
        tables.forEach(table => {
            this.makeTableSortable(table);
        });
    }

    makeTableSortable(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const column = header.getAttribute('data-sort');
                const currentOrder = header.getAttribute('data-order') || 'asc';
                const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                
                // Update all headers
                headers.forEach(h => h.removeAttribute('data-order'));
                header.setAttribute('data-order', newOrder);
                
                // Reload page with new sort order
                const url = new URL(window.location);
                url.searchParams.set('sort', column);
                url.searchParams.set('order', newOrder);
                this.loadPage(url.toString());
            });
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.classList.add('modal-open');
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    displayValidationErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(error => error.remove());
        
        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                errorDiv.textContent = errors[field];
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    refreshPage() {
        window.location.reload();
    }

    getBulkActionConfirmMessage(action) {
        const messages = {
            'delete': 'آیا از حذف آیتم‌های انتخاب شده اطمینان دارید؟',
            'activate': 'آیا از فعال‌سازی آیتم‌های انتخاب شده اطمینان دارید؟',
            'deactivate': 'آیا از غیرفعال‌سازی آیتم‌های انتخاب شده اطمینان دارید؟',
            'export': 'آیا می‌خواهید آیتم‌های انتخاب شده را صادر کنید؟'
        };
        
        return messages[action] || 'آیا از انجام این عمل اطمینان دارید؟';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminManager();
});
