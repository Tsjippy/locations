<?php

namespace TSJIPPY\LOCATIONS;

/**
 * Plugin Name:          Tsjippy Locations
 * Description:          This plugin adds a custom post type 'locations'.Locations can be used to share shops, hotels ministries etc. They will bevisible on a map. It adds one shortcode: <code>[ministry_description name=SOMENAME]</code>
 * Version:              10.3.8
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         7.0
 * Plugin URI:            https://github.com/Tsjippy/
 * Tested:               7.0
 * TextDomain:            tsjippy
 * Requires Plugins:    , ultimate-maps-by-supsystic
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_locations_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () {
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }

    // add an extra form setting column in db
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $forms    = new \TSJIPPY\FORMS\Forms();

    maybe_add_column($forms->tableName, 'google_maps_api', "ALTER TABLE $forms->tableName ADD COLUMN `google_maps_api` bool");

    if(function_exists('TSJIPPY\activate')){
        \TSJIPPY\activate();
    }
});