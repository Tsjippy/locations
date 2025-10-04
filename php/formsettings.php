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
    <input type='checkbox' name='google_maps_api' value='1' <?php echo $checked;?>> Use Google Maps Api on this form
    <?php
}

add_filter('sim-forms-before-saving-settings', __NAMESPACE__.'\beforeSavingSettings', 10, 3);
function beforeSavingSettings($newSettings, $object, $formId){
    global $Modules;

    $mapsApi                        = isset($_POST['google_maps_api'])   ? true : false;
    $newSettings['google_maps_api']	= $mapsApi;

    if($mapsApi){
        if(!isset($Modules[MODULE_SLUG]['google_maps_api_forms'])){
            $Modules[MODULE_SLUG]['google_maps_api_forms']   = [];
        }

        $Modules[MODULE_SLUG]['google_maps_api_forms'][] = $formId;
        update_option('sim_modules', $Modules);
    }

    return $newSettings;
}

add_filter('forms-form-table-formats', __NAMESPACE__.'\addFormFormat', 10, 2);
function addFormFormat($formats, $object){
    $formats['google_maps_api']  = '%d';

    return $formats;
}