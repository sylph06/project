(function (window) {
    'use strict';

    window.AppAlerts = {
        show: function (message, icon, title) {
            return window.Swal.fire({
                title: title || '',
                text: String(message),
                icon: icon || 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#198754'
            });
        },
        success: function (message) {
            return this.show(message, 'success');
        },
        error: function (message) {
            return this.show(message, 'error', 'Something went wrong');
        },
        warning: function (message) {
            return this.show(message, 'warning');
        },
        confirm: function (message, title, confirmButtonText) {
            return window.Swal.fire({
                title: title || 'Are you sure?',
                text: String(message),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmButtonText || 'Continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then(function (result) {
                return result.isConfirmed;
            });
        }
    };
})(window);
