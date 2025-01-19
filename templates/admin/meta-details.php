<div class="wrap">
    <h2><?php esc_html_e('Meta Details', 'metafiller'); ?></h2>
    <button id="generate-metas-btn" class="button button-primary">
        <?php esc_html_e('Generate Metas Regarding Content', 'metafiller'); ?>
    </button>

    <?php
    // Use MetaTableManager to render the detailed table
    \Metafiller\Admin\MetaTableManager::renderDetailedTable();
    ?>


</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('generate-metas-btn');
        if (!button) return;

        button.addEventListener('click', function () {
            const agree = confirm(
                "<?php echo esc_js(__('Do you agree to use OpenAI to generate metadata for your content?', 'metafiller')); ?>"
            );
            if (!agree) {
                return;
            }

            // Prepare AJAX request
            const formData = new FormData();
            formData.append('action', 'metafiller_generate_metas'); // WordPress AJAX action
            formData.append('nonce', "<?php echo esc_js(wp_create_nonce('metafiller_generate_metas')); ?>");

            fetch(ajaxurl, {
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
                        alert(
                            "<?php echo esc_js(__('Metadata generation complete.', 'metafiller')); ?>"
                        );
                    } else {
                        alert(
                            "<?php echo esc_js(__('Error: ', 'metafiller')); ?>" + data.data.message
                        );
                    }
                })
                .catch((error) => {
                    alert(
                        "<?php echo esc_js(__('Error: Could not generate metadata. ', 'metafiller')); ?>" +
                        error.message
                    );
                });
        });
    });
</script>
