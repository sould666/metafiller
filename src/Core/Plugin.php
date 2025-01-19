<?php

namespace Metafiller\Core;

use Metafiller\Admin\AdminMenu;
use Metafiller\Admin\AjaxHandler;

use Metafiller\Admin\OpenAiHandler;
use Metafiller\Admin\SeoCheck;

class Plugin {
	public static function init() {
		// Initialize Admin Menu
		add_action( 'admin_menu', array( AdminMenu::class, 'registerMenu' ) );
		// Initialize AJAX Handler
		new AjaxHandler();
	}
}
