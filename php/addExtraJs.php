<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('tsjippy-forms-extra-js', __NAMESPACE__ . '\addJs', 10, 3);

/**
 * Add extra JavaScript for the user location form
 * @param string    $js            The existing JavaScript code for the form
 * @param string    $formName
 * @param bool      $minimized      Whether to load the minimized version of the JavaScript file
 * @return string                   The updated JavaScript code with the extra code added
 */
function addJs($js, $formName, $minimized)
{
    if ($formName != 'user_location') {
        return $js;
    }

    $path    = plugin_dir_path(__DIR__) . "js/{$formName}.min.js";
    if (!$minimized || !file_exists($path)) {
        $path    = plugin_dir_path(__DIR__) . "js/{$formName}.js";
    }

    if (file_exists($path)) {
        $js        = file_get_contents($path);
    }

    return $js;
}
