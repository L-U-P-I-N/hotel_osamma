<!-- Toast Notifications Container -->
<div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3 pointer-events-none" dir="rtl">
    <!-- Toast templates will be inserted here -->
</div>

<script>
    // Toast System - Global
    window.Toast = {
        show: function(message, type = 'success', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();

            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-amber-500 text-white',
                info: 'bg-blue-500 text-white'
            };

            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `${colors[type] || colors.info} rounded-lg px-4 py-3 shadow-lg flex items-center gap-3 min-w-[300px] pointer-events-auto animate-slideInRight`;
            toast.innerHTML = `
                <span class="text-lg font-bold">${icons[type]}</span>
                <span class="flex-1">${message}</span>
                <button onclick="Toast.close('${toastId}')" class="text-lg font-bold opacity-70 hover:opacity-100">×</button>
            `;

            container.appendChild(toast);

            // Auto-hide after duration
            setTimeout(() => {
                Toast.close(toastId);
            }, duration);
        },

        close: function(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('animate-slideOutRight');
                setTimeout(() => toast.remove(), 300);
            }
        },

        success: function(msg) { this.show(msg, 'success'); },
        error: function(msg) { this.show(msg, 'error', 4000); },
        warning: function(msg) { this.show(msg, 'warning', 3500); },
        info: function(msg) { this.show(msg, 'info'); }
    };
</script>

<style>
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .animate-slideInRight {
        animation: slideInRight 0.3s ease-out;
    }

    .animate-slideOutRight {
        animation: slideOutRight 0.3s ease-in;
    }
</style>
