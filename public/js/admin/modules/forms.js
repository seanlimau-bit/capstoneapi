
// ============================================================================
// public/js/admin/modules/forms.js - REUSABLE FORM HANDLING
// ============================================================================
class AdminForms {
    static async submit(formElement, options = {}) {
        if (typeof formElement === 'string') {
            formElement = document.getElementById(formElement);
        }

        const ajax = new AdminAjax();
        const formData = new FormData(formElement);
        const submitButton = formElement.querySelector('[type="submit"]');

        try {
            if (submitButton) ajax.setLoadingState(submitButton, true);

            const response = await ajax.create(
                formElement.action || window.location.href, 
                formData,
                options
            );

            if (response.success) {
                AdminToast.show(response.message || 'Operation completed successfully', 'success');
                
                if (options.onSuccess) {
                    options.onSuccess(response);
                } else if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.reload();
                }
            }

            return response;
        } finally {
            if (submitButton) ajax.setLoadingState(submitButton, false);
        }
    }
}
