<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('sim_forms_module_update', __NAMESPACE__.'\afterUpdate');
function afterUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $simForms = new SIM\FORMS\SimForms();

    SIM\printArray($oldVersion);

    if($oldVersion < '2.47.0'){
        maybe_add_column($simForms->tableName, 'google_maps_api', "ALTER TABLE $simForms->tableName ADD COLUMN `google_maps_api` bool");	
    }
}