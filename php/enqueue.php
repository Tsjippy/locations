<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-forms-before-showing-form', __NAMESPACE__ . '\beforeShowingForm');
/**
 * Load the loacations css
 * 
 * @param string $html
 */
function beforeShowingForm($html)
{
    wp_enqueue_style('tsjippy_locations_style');

    return $html;
}

add_filter( 'script_module_data_@tsjippy/forms_dynamic_user-locations_js', function($data){
    $apiKey = SETTINGS['google-maps-api-key'] ?? '';

    if ($apiKey) {
        //Get current users location
        $location = get_user_meta(wp_get_current_user()->ID, 'tsjippy_location', true);
        if (isset($location['address'])) {
            $address = $location['address'];
        } else {
            $address = "";
        }

        $locations    = apply_filters('tsjippy-locations-array', []);

        $data['locations'] = array(
            'address'   => $address,
            'locations' => $locations,
        );

        $data['mapsApi'] = ['key' => $apiKey];
    }

    return $data; 
} );
