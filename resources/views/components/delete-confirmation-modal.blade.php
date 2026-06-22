<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4" dir="rtl">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-200 ease-out scale-95 opacity-0 pointer-events-none" id="delete-modal-content">
        <div class="p-6 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">هل أنت متأكد؟</h3>
            <p class="text-sm text-gray-600 mb-6" id="delete-modal-message">
                هذا الإجراء لا يمكن التراجع عنه.
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="deleteModal.close()"
                    class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    إلغاء
                </button>
                <button type="button" onclick="deleteModal.confirm()"
                    class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                    حذف
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.deleteModal = {
        form: null,
        messageElement: null,

        open: function(formElement, customMessage = null) {
            const modal = document.getElementById('delete-modal');
            const content = document.getElementById('delete-modal-content');
            const message = document.getElementById('delete-modal-message');

            this.form = formElement;
            if (customMessage) {
                message.textContent = customMessage;
            } else {
                message.textContent = 'هذا الإجراء لا يمكن التراجع عنه. هل تريد المتابعة؟';
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0', 'pointer-events-none');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        },

        close: function() {
            const modal = document.getElementById('delete-modal');
            const content = document.getElementById('delete-modal-content');

            content.classList.add('scale-95', 'opacity-0', 'pointer-events-none');
            content.classList.remove('scale-100', 'opacity-100');

            setTimeout(() => {
                modal.classList.add('hidden');
                this.form = null;
            }, 200);
        },

        confirm: function() {
            if (this.form) {
                this.form.submit();
            }
        }
    };

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            deleteModal.close();
        }
    });
</script>

<style>
    #delete-modal.hidden {
        display: none !important;
    }
</style>
