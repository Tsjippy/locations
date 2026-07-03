<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-forms-before-showing-form', __NAMESPACE__ . '\beforeShowingForm', 10, 2);
function beforeShowingForm($html, $object)
{
    $googleApiForms = SETTINGS['google-maps-api-forms'] ?? [];
    if (isset($googleApiForms[$object->formData->id])) {
        add_action('wp_enqueue_scripts', function () {
            addGoogleMapsApiKey();
        }, 99);
    }

    wp_enqueue_style('tsjippy_locations_style');

    return $html;
}

function addGoogleMapsApiKey()
{
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

        wp_localize_script(
            'tsjippy_locations_script',
            'locations',
            array(
                'address'         => $address,
                'locations'        => $locations,
            )
        );

        wp_localize_script(
            'tsjippy_locations_script',
            'mapsApi',
            ['key' => $apiKey]
        );
    }
}
