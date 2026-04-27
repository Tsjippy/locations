<?php
namespace TSJIPPY\LOCATIONS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('tsjippy_form_extra_js', __NAMESPACE__.'\addJs', 10, 3);
function addJs($js, $object, $minimized){
	if($object->formName != 'user_location' ){
		return $js;
	}
	
	$path	= plugin_dir_path( __DIR__)."js/$object->formName.min.js";
	if(!$minimized || !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/$object->formName.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}