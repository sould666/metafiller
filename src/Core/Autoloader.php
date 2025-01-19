<?php

namespace Metafiller\Core;

class Autoloader {
    /**
     * Registers the autoloader function.
     */
    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ], true,true );
    }

    /**
     * Autoload function to include the required class file.
     *
     * @param string $class Fully-qualified class name.
     */
    private static function autoload( $class ) {
        // Check if the class belongs to the Metafiller namespace
        if ( strpos( $class, 'Metafiller\\' ) !== 0 ) {
            return;
        }

        // Convert namespace to file path
        $relative_class = str_replace( 'Metafiller\\', '', $class );
        $file = METAFILLER_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

        // Include the file if it exists
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
