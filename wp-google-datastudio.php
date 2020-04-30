<?php
/**
 * Plugin Name: Google Data Studio
 * Description: Displays Data Studio dashboard in WordPress administration panel.
 * Plugin URI: https://github.com/innocode-digital/wp-google-datastudio
 * Version: 1.0.1
 * Author: Innocode
 * Author URI: https://innocode.com
 * Tested up to: 5.4
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

use Innocode\GoogleDataStudio;

define( 'INNOCODE_GOOGLE_DATASTUDIO_VERSION', '1.0.1' );
define( 'INNOCODE_GOOGLE_DATASTUDIO_FILE', __FILE__ );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$innocode_google_datastudio = new GoogleDataStudio\Plugin( __DIR__ );
$innocode_google_datastudio->run();

$GLOBALS['innocode_google_datastudio'] = $innocode_google_datastudio;

if ( ! function_exists( 'innocode_google_datastudio' ) ) {
    function innocode_google_datastudio() {
        global $innocode_google_datastudio;

        return $innocode_google_datastudio;
    }
}
