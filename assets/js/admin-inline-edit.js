jQuery(document).ready(function ($) {
    // Enable editing on click
    $('.meta-column.editable').on('click', function () {
        const $cell = $(this);

        if ($cell.find('input').length > 0) return; // Avoid duplicate inputs

        const originalValue = $cell.text().trim();
        const postId = $cell.data('id');
        const field = $cell.data('field');
        const maxLength = parseInt($cell.data('max-length'), 10) || 0;
        const isTerm = $cell.data('is-term') === 'true';
        const taxonomy = $cell.data('taxonomy');

        // Replace text with input field and character counter
        $cell.html(`
            <input type="text" class="inline-edit-input" value="${originalValue}" maxlength="${maxLength}" />
            <span class="char-counter">${originalValue.length}/${maxLength}</span>
        `);

        const $input = $cell.find('input');
        const $counter = $cell.find('.char-counter');

        // Focus the input field
        $input.focus();

        // Update character counter dynamically
        $input.on('input', function () {
            const currentLength = $input.val().length;
            $counter.text(`${currentLength}/${maxLength}`);
            if (currentLength > maxLength) {
                $counter.css('color', 'red');
            } else {
                $counter.css('color', '');
            }
        });

        // Save on blur or Enter key
        $input.on('blur keydown', function (e) {
            if (e.type === 'blur' || e.key === 'Enter') {
                const newValue = $input.val().trim();

                if (newValue === originalValue || newValue.length > maxLength) {
                    $cell.html(originalValue); // Revert to original if unchanged or invalid
                    return;
                }

                // Show loading state
                $cell.html('<span class="loading">Saving...</span>');

                // Make AJAX request to save the data
                $.ajax({
                    url: metafiller_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'metafiller_save_meta_field',
                        nonce: metafiller_ajax.nonce,
                        post_id: postId,
                        field: field,
                        value: newValue,
                        is_term: isTerm,
                        taxonomy: taxonomy,
                    },
                    success: function (response) {
                        if (response.success) {
                            $cell.html(newValue); // Update cell with new value
                        } else {
                            alert(response.data.message || 'Failed to update meta field.');
                            $cell.html(originalValue); // Revert to original value
                        }
                    },
                    error: function () {
                        alert('An error occurred. Please try again.');
                        $cell.html(originalValue); // Revert to original value
                    }
                });
            }
        });
    });
});
