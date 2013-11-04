<?php

/*
 * Plugin Name: Jetpack Modules
 * Plugin URI: http://github.com/georgestephanis/jetpack-modules
 * Description: Adds a 'modules' page to Jetpack.
 * Author: George Stephanis
 * Version: 1.0
 * Author URI: http://stephanis.info/
 */

add_action( 'init', 'load_jetpack_modules_class' );
function load_jetpack_modules_class() {
    if ( is_admin() && class_exists( 'Jetpack' ) ) {
        require_once( dirname(__FILE__) . '/class.jetpack-modules.php' );
    }
}
