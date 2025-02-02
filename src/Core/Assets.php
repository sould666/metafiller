<?php

namespace Metafiller\Core;

class Assets {
	public static function enqueue() {
		// Enqueue main JavaScript
		wp_enqueue_script(
			'metafiller-main-js',
			METAFILLER_PLUGIN_URL . 'assets/js/main.js',
			array( 'jquery' ),
			'1.0',
			true
		);

		// Enqueue inline edit JavaScript
		wp_enqueue_script(
			'admin-inline-edit',
			METAFILLER_PLUGIN_URL . 'assets/js/admin-inline-edit.js',
			array( 'jquery' ),
			'1.0',
			true
		);
        // Enqueue inline Meta Details Admin tab JavaScript
		wp_enqueue_script(
			'meta-details',
			METAFILLER_PLUGIN_URL . 'assets/js/meta-details.js',
			array( 'jquery' ),
			'1.0',
			true
		);

		// Localize script to provide nonce and AJAX URL
		wp_localize_script(
			'admin-inline-edit',
			'metafiller_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'metafiller_save_meta_field' ),
			)
		);

        // Localize meta-details.js
        wp_localize_script(
            'meta-details',
            'metafiller_vars',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('metafiller_generate_metas'),
                'confirm_message' => esc_js(__('Do you agree to use OpenAI to generate metadata for your content?', 'metafiller')),
                'success_message' => esc_js(__('Metadata generation complete.', 'metafiller')),
                'error_message' => esc_js(__('Error: Could not generate metadata. ', 'metafiller'))
            )
        );

		// Enqueue admin styles
		wp_enqueue_style(
			'metafiller-admin-css',
			METAFILLER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			'1.0'
		);
	}
}
