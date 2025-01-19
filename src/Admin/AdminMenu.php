<?php

namespace Metafiller\Admin;

class AdminMenu {
	public static function registerMenu() {
		add_menu_page(
			'Metafiller Dashboard',
			'Metafiller',
			'manage_options',
			'metafiller_dashboard',
			array( Dashboard::class, 'renderDashboard' ),
			'dashicons-admin-generic'
		);
	}
}
