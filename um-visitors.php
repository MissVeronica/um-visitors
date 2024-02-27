<?php
/**
 * Plugin Name:     Ultimate Member - User Visitors and Visits
 * Description:     Extension to Ultimate Member for the display of User Profile Visitors and User Profile Visits. This extension can't update many of the visits when the profile page is cached by web hosting or a WP plugin.
 * Version:         0.5.0 Development beta
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     um-visitors
 * Domain Path:     /languages
 * UM version:      2.8.3
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' )) return;

require_once( ABSPATH.'wp-admin/includes/plugin.php' );

define( 'plugin_visitors_file',   __FILE__ );
define( 'um_visitors_path',        plugin_dir_path( __FILE__ ) );
define( 'um_visitors_textdomain', 'um-visitors' );

add_action( 'plugins_loaded', 'um_visitors_plugins_loaded', 0 );

function um_visitors_plugins_loaded() {

    $locale = ( get_locale() != '' ) ? get_locale() : 'en_US';
    load_textdomain( um_visitors_textdomain, WP_LANG_DIR . '/plugins/' . um_visitors_textdomain . '-' . $locale . '.mo' );
    load_plugin_textdomain( um_visitors_textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    if ( is_admin()) {

        require_once( um_visitors_path . 'includes/admin/class-visitors-admin.php' );

        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            require_once( um_visitors_path . 'includes/core/class-um-visitors-directory.php' );
        }

    } else {

        if ( UM()->options()->get( 'visitors_active' ) == 1 ) {

            require_once( um_visitors_path . 'includes/core/class-um-visitors-user.php' );
            require_once( um_visitors_path . 'includes/core/class-um-visitors-options.php' );
        }
    }
}
