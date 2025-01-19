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

		// Localize script to provide nonce and AJAX URL
		wp_localize_script(
			'admin-inline-edit',
			'metafiller_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'metafiller_save_meta_field' ),
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
