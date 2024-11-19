<?php
namespace SIM\LOCATIONS;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\loadAssets', 99);
function loadAssets(){
    wp_register_style('sim_locations_style', SIM\pathToUrl(MODULE_PATH.'css/locations.min.css'), array(), MODULE_VERSION);
    wp_register_style('sim_employee_style', SIM\pathToUrl(MODULE_PATH.'css/employee.min.css'), array(), MODULE_VERSION);

    // frontend content of profile page
	$pages   = SIM\getModuleOption('frontendposting', 'front_end_post_pages');
    if(in_array('location', (array)SIM\getModuleOption('usermanagement', 'enabled-forms'))){
        $pages   = array_merge($pages, SIM\getModuleOption('usermanagement', 'account_page'));
    }

    if(is_numeric(get_the_ID()) && in_array(get_the_ID(), $pages)){
        wp_enqueue_style('sim_locations_style');
        
        addGoogleMapsApiKey();
    }
}

add_filter('sim-forms-before-showing-form', __NAMESPACE__.'\beforeShowingForm', 10, 2);
function beforeShowingForm($html, $object){
    if(in_array($object->formData->id, (array) SIM\getModuleOption(MODULE_SLUG, 'google_maps_api_forms' ))){
        $html   .= "<script>
            function initMap(){
	            console.log('Google Maps loaded');
            }
        </script>";

        add_action( 'wp_enqueue_scripts', function(){
            addGoogleMapsApiKey();
        }, 99);
    }

    return $html;
}

function addGoogleMapsApiKey(){
    $apiKey = SIM\getModuleOption(MODULE_SLUG, 'google-maps-api-key');

    if($apiKey){
        //Get current users location
        $location = get_user_meta( wp_get_current_user()->ID, 'location', true );
        if (isset($location['address'])){
            $address = $location['address'];
        }else{
            $address = "";
        }

        $locations	= apply_filters('sim-locations-array', []);

        wp_localize_script( 'sim_script',
            'locations',
            array(
                'address' 		=> $address,
                'locations'		=> $locations,
            )
        );

        ?>
        <script>
            function initMap(){
	            console.log('Google Maps loaded');
            }
        </script>
        <?php
        
        wp_localize_script( 'sim_script',
            'mapsApi',
            ['key'=>$apiKey]
        );
    }
}