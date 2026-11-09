<?php
// Toast Notification System
function show_toast($message, $type = 'success') {
    $_SESSION['toast'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_toast() {
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        unset($_SESSION['toast']);
        return $toast;
    }
    return null;
}

// Toast Notification HTML
function render_toast() {
    $toast = get_toast();
    if ($toast) {
        $icons = [
            'success' => 'check-circle',
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle'
        ];
        $type = is_string($toast['type'] ?? null) && isset($icons[$toast['type']])
            ? $toast['type']
            : 'info';
        $icon = $icons[$type];
        $message = is_scalar($toast['message'] ?? null)
            ? htmlspecialchars((string) $toast['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';
        
        return "
        <div class='toast toast-{$type}' role='alert'>
            <div class='toast-icon'>
                <i class='fas fa-{$icon}'></i>
            </div>
            <div class='toast-content'>
                {$message}
            </div>
            <button class='toast-close' onclick='this.parentElement.remove()'>
                <i class='fas fa-times'></i>
            </button>
        </div>";
    }
    return '';
}

// Loading State
function show_loading() {
    return "
    <div class='loading-overlay'>
        <div class='loading-spinner'>
            <i class='fas fa-spinner fa-spin'></i>
        </div>
    </div>";
}

// Toast Styles
function get_toast_styles() {
    return "
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-in-out;
            min-width: 300px;
            max-width: 400px;
        }
        .toast-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .toast-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .toast-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .toast-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .toast-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        .toast-content {
            flex: 1;
        }
        .toast-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0;
            margin-left: 12px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .toast-close:hover {
            opacity: 1;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            font-size: 40px;
            color: #007BFF;
        }
    </style>";
}

// Toast JavaScript
function get_toast_script() {
    return <<<'HTML'
    <script>
        function showToast(message, type = 'success') {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };
            if (!Object.prototype.hasOwnProperty.call(icons, type)) {
                type = 'info';
            }

            const toast = document.createElement('div');
            toast.classList.add('toast', `toast-${type}`);
            toast.setAttribute('role', 'alert');

            const iconContainer = document.createElement('div');
            iconContainer.className = 'toast-icon';
            const icon = document.createElement('i');
            icon.classList.add('fas', `fa-${icons[type]}`);
            iconContainer.appendChild(icon);

            const content = document.createElement('div');
            content.className = 'toast-content';
            content.textContent = String(message);

            const closeButton = document.createElement('button');
            closeButton.className = 'toast-close';
            closeButton.setAttribute('aria-label', 'Close notification');
            closeButton.addEventListener('click', () => toast.remove());
            const closeIcon = document.createElement('i');
            closeIcon.classList.add('fas', 'fa-times');
            closeButton.appendChild(closeIcon);

            toast.append(iconContainer, content, closeButton);
            
            const container = document.querySelector('.toast-container') || createToastContainer();
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
            return container;
        }
        
        // Show loading overlay
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class='loading-spinner'>
                    <i class='fas fa-spinner fa-spin'></i>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        
        // Hide loading overlay
        function hideLoading() {
            const overlay = document.querySelector('.loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    </script>
HTML;
}

// Initialize notifications
function init_notifications() {
    echo get_toast_styles();
    echo get_toast_script();
    
    $toast = render_toast();
    if ($toast) {
        echo "<div class='toast-container'>{$toast}</div>";
    }
} 