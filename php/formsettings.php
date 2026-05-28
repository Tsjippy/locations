<?php
namespace TSJIPPY\LOCATIONS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('tsjippy-forms-extra-form-settings', __NAMESPACE__.'\extraFormSettings');
function extraFormSettings($object){
    $checked    = '';
    if(!empty($object->formData->googlemapsapi)){
        $checked = 'checked';
    }
    ?>
    <br>
    <h4>Google Maps Api</h4>
    <input type='checkbox' name='google-maps-api' value='1' <?php echo $checked;?>> Use Google Maps Api on this form
    <?php
}

add_filter('tsjippy-forms-before-saving-settings', __NAMESPACE__.'\beforeSavingSettings', 10, 3);
function beforeSavingSettings($settings, $object, $formId){
    $mapsApi                        = isset($_POST['google-maps-api'])   ? true : false;
    $settings['google_maps_api']	= $mapsApi;

    if($mapsApi){
        $forms  = SETTINGS['google-maps-api-forms'] ?? false;

        $forms[]  = $formId;
        
        $settings   = SETTINGS;
        $settings['google-maps-api-forms'] = $forms;

        update_option('tsjippy_locations_settings', $settings);
    }

    return $settings;
}

add_filter('forms-form-table-formats', __NAMESPACE__.'\addFormFormat', 10, 2);
function addFormFormat($formats, $object){
    $formats['google_maps_api']  = '%d';

    return $formats;
}