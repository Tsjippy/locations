<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('sim-forms-extra-form-settings', __NAMESPACE__.'\extraFormSettings');
function extraFormSettings($object){
    $checked    = '';
    if($object->formData->googlemapsapi){
        $checked = 'checked';
    }
    ?>
    <br>
    <h4>Google Maps Api</h4>
    <input type='checkbox' name='google-maps-api' value='1' <?php echo $checked;?>> Use Google Maps Api on this form
    <?php
}

add_filter('sim-forms-before-saving-settings', __NAMESPACE__.'\beforeSavingSettings', 10, 3);
function beforeSavingSettings($settings, $object, $formId){
    $mapsApi                        = isset($_POST['google-maps-api'])   ? true : false;
    $settings['google_maps_api']	= $mapsApi;

    if($mapsApi){
        $forms  = SIM\getModuleOption(MODULE_SLUG, 'google-maps-api-forms', false);

        $forms[]  = $formId;

        SIM\updateModuleOptions(MODULE_SLUG, $forms, 'google-maps-api-forms');
    }

    return $settings;
}

add_filter('forms-form-table-formats', __NAMESPACE__.'\addFormFormat', 10, 2);
function addFormFormat($formats, $object){
    $formats['google_maps_api']  = '%d';

    return $formats;
}