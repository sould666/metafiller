<?php
/*
Plugin Name: MetaFiller
Description: A plugin to generate and populate SEO meta fields using AI.
Version: 1.0
Author: Sould
Text Domain: metafiller
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

namespace Metafiller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define essential constants
define( 'METAFILLER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Absolute path to the plugin directory
define( 'METAFILLER_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // URL to the plugin directory

// Require Composer's autoloader
if ( file_exists( METAFILLER_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once METAFILLER_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	wp_die(
		esc_html__( 'Composer dependencies are not installed. Please run "composer install" in the plugin directory.', 'metafiller' )
	);
}

// Require autoloader
require_once METAFILLER_PLUGIN_DIR . 'src/Core/Autoloader.php';



use Metafiller\Core\Plugin;
use Metafiller\Core\Autoloader;
use Metafiller\Admin\SeoCheck;
use Metafiller\Core\Assets;

// Register the autoloader
Autoloader::register();

add_action( 'admin_enqueue_scripts', array( Assets::class, 'enqueue' ) );

Plugin::init();

// Register activation hook
register_activation_hook( __FILE__, array( SeoCheck::class, 'onActivation' ) );
