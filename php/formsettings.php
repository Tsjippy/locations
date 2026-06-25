<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-forms-extra-form-settings', __NAMESPACE__ . '\extraFormSettings');
function extraFormSettings($object)
{
    $checked    = '';
    if (!empty($object->formData->googlemapsapi)) {
        $checked = 'checked';
    }
?>
    <br>
    <h4>Google Maps Api</h4>
    <input type='checkbox' name='google-maps-api' value='1' <?php echo $checked; ?>> Use Google Maps Api on this form
<?php
}

add_filter('tsjippy-forms-before-saving-settings', __NAMESPACE__ . '\beforeSavingSettings', 10, 3);
function beforeSavingSettings($request, $object, $formId)
{
    $request['google_maps_api'] = isset($request['google-maps-api'])   ? true : false;

    if ($request['google_maps_api']) {
        $forms   = SETTINGS['google-maps-api-forms'] ?? [];

        $forms[]  = $formId;

        $settings   = SETTINGS;
        $request['google-maps-api-forms'] = $forms;

        update_option('tsjippy_locations_settings', $settings);
    }

    return $request;
}

add_filter('tsjippy-forms-form-table-formats', __NAMESPACE__ . '\addFormFormat', 10, 2);
function addFormFormat($formats, $object)
{
    $formats['google_maps_api']  = '%d';

    return $formats;
}
