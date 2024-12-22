document.addEventListener('DOMContentLoaded', function() {
    function showAlert(type, title, text, confirmButtonText = 'OK') {
        Swal.fire({
            icon: type,
            title: title,
            text: text,
            confirmButtonText: confirmButtonText,
            customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                content: 'custom-swal-content',
                confirmButton: 'custom-swal-confirm-button'
            }
        });
    }

    window.showSuccessAlert = function(message, title = 'Succès') {
        showAlert('success', title, message);
    };

    window.showErrorAlert = function(message, title = 'Erreur') {
        showAlert('error', title, message);
    };

    window.showWarningAlert = function(message, title = 'Attention') {
        showAlert('warning', title, message);
    };

    window.showInfoAlert = function(message, title = 'Information') {
        showAlert('info', title, message);
    };

    window.showConfirmAlert = function(message, onConfirm, title = 'Êtes-vous sûr ?') {
        Swal.fire({
            icon: 'question',
            title: title,
            text: message,
            showCancelButton: true,
            confirmButtonText: 'Oui',
            cancelButtonText: 'Annuler',
            customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                content: 'custom-swal-content',
                confirmButton: 'custom-swal-confirm-button',
                cancelButton: 'custom-swal-cancel-button'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    };
});
