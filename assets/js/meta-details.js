document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('generate-metas-btn');
    if (!button) return;

    button.addEventListener('click', function () {
        const agree = confirm(metafiller_vars.confirm_message);
        if (!agree) {
            return;
        }

        // Prepare AJAX request
        const formData = new FormData();
        formData.append('action', 'metafiller_generate_metas'); // WordPress AJAX action
        formData.append('nonce', metafiller_vars.nonce);

        fetch(metafiller_vars.ajaxurl, {
            method: 'POST',
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    alert(metafiller_vars.success_message);
                } else {
                    alert(metafiller_vars.error_message + data.data.message);
                }
            })
            .catch((error) => {
                alert(metafiller_vars.error_message + error.message);
            });
    });
});
