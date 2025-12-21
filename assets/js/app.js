/**
 * 2SureSub - Main JavaScript
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize modals
    initModals();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize copy buttons
    initCopyButtons();
}

// Utility Functions
const Utils = {
    formatMoney: (amount) => {
        return '₦' + parseFloat(amount).toLocaleString('en-NG', { minimumFractionDigits: 2 });
    },
    
    formatPhone: (phone) => {
        return phone.replace(/[^0-9]/g, '');
    },
    
    showLoader: (container) => {
        container.innerHTML = '<div class="flex justify-center py-8"><div class="spinner"></div></div>';
    },
    
    hideLoader: (container) => {
        const spinner = container.querySelector('.spinner');
        if (spinner) spinner.remove();
    },
    
    debounce: (func, wait) => {
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
};

// Toast Notifications
const Toast = {
    show: (message, type = 'info', duration = 5000) => {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg ${colors[type]} text-white toast-enter`;
        toast.innerHTML = `
            <i class="fas ${icons[type]}"></i>
            <span class="font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-2 hover:opacity-75">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), duration);
    },
    
    success: (message) => Toast.show(message, 'success'),
    error: (message) => Toast.show(message, 'error'),
    warning: (message) => Toast.show(message, 'warning'),
    info: (message) => Toast.show(message, 'info')
};

// Modal System
function initModals() {
    // Close modal on backdrop click
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal-backdrop:not(.hidden)');
            if (openModal) closeModal(openModal.id);
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Form Validation
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    
    // Clear previous errors
    form.querySelectorAll('.error-message').forEach(el => el.remove());
    form.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
    });
    
    // Required fields
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }
    });
    
    // Email validation
    form.querySelectorAll('[type="email"]').forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email');
            isValid = false;
        }
    });
    
    // Phone validation
    form.querySelectorAll('[data-validate-phone]').forEach(field => {
        if (field.value && !isValidPhone(field.value)) {
            showFieldError(field, 'Please enter a valid phone number');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('border-red-500');
    const error = document.createElement('p');
    error.className = 'error-message text-red-500 text-sm mt-1';
    error.textContent = message;
    field.parentNode.appendChild(error);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    const cleaned = phone.replace(/[^0-9]/g, '');
    return cleaned.length >= 10 && cleaned.length <= 14;
}

// Tooltips
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-3 py-2 text-sm bg-gray-900 text-white rounded-lg shadow-lg tooltip';
    tooltip.textContent = text;
    tooltip.style.top = (e.target.offsetTop - 40) + 'px';
    tooltip.style.left = e.target.offsetLeft + 'px';
    e.target.appendChild(tooltip);
}

function hideTooltip(e) {
    const tooltip = e.target.querySelector('.tooltip');
    if (tooltip) tooltip.remove();
}

// Copy to Clipboard
function initCopyButtons() {
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(() => {
                Toast.success('Copied to clipboard!');
            });
        });
    });
}

// AJAX Request Helper
const API = {
    request: async (url, options = {}) => {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Error:', error);
            Toast.error('Something went wrong. Please try again.');
            throw error;
        }
    },
    
    get: (url) => API.request(url, { method: 'GET' }),
    
    post: (url, data) => API.request(url, {
        method: 'POST',
        body: JSON.stringify(data)
    })
};

// Network Selection
function selectNetwork(networkId, element) {
    document.querySelectorAll('.network-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-primary-500');
    });
    element.classList.add('ring-2', 'ring-primary-500');
    
    // Load plans for selected network
    if (typeof loadPlans === 'function') {
        loadPlans(networkId);
    }
}

// Plan Selection
function selectPlan(planId, element) {
    document.querySelectorAll('.plan-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    
    // Update hidden input
    const input = document.querySelector('input[name="plan_id"]');
    if (input) input.value = planId;
}

// Confirm Dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Counter Animation
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(start).toLocaleString();
        }
    }, 16);
}

// Initialize counters on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const target = parseInt(entry.target.getAttribute('data-count'));
            animateCounter(entry.target, target);
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
