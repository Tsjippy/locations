<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets', 99);
function loadAssets()
{
    wp_register_style('tsjippy_locations_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/locations.min.css'), array(), PLUGINVERSION);
    wp_register_style('tsjippy_employee_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/employee.min.css'), array(), PLUGINVERSION);

    wp_enqueue_script('tsjippy_locations_script', plugins_url('js/locations.min.js', __DIR__), [], PLUGINVERSION, true);

    // frontend content of profile page
    if (defined('TSJIPPY\FRONTENDPOSTING\SETTINGS')) {
        $frontEndPage   = TSJIPPY\FRONTENDPOSTING\SETTINGS['front-end-post-page'] ?? '';
        $accountPage    = '';

        if (defined('TSJIPPY\USERMANAGEMENT\SETTINGS') && in_array('location', TSJIPPY\USERMANAGEMENT\SETTINGS['enabled-forms'] ?? [])) {
            $accountPage = TSJIPPY\USERMANAGEMENT\SETTINGS['account_page'] ?? '';
        }

        if (is_numeric(get_the_ID()) && in_array(get_the_ID(), [$frontEndPage, $accountPage])) {
            wp_enqueue_style('tsjippy_locations_style');

            addGoogleMapsApiKey();
        }
    }
}

add_filter('tsjippy-forms-before-showing-form', __NAMESPACE__ . '\beforeShowingForm', 10, 2);
function beforeShowingForm($html, $object)
{
    if (in_array($object->formData->id, SETTINGS['google-maps-api-forms'] ?? [])) {
        add_action('wp_enqueue_scripts', function () {
            addGoogleMapsApiKey();
        }, 99);
    }

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
