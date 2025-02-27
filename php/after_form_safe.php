<?php
namespace SIM\LOCATIONS;
use SIM;

// Update marker icon when family picture is updated
add_action('sim_update_family_picture', __NAMESPACE__.'\familiPicture', 10, 2);
function familiPicture($userId, $attachmentId){
    $maps       = new Maps();
    $markerId 	= get_user_meta($userId, "marker_id", true);
    $url        = wp_get_attachment_url($attachmentId);
    $iconTitle	= SIM\getFamilyName($userId);
    
    $maps->createIcon($markerId, $iconTitle, $url, 1);

    //Save the marker id for all family members
    $partnerId    = SIM\hasPartner($userId);
	if(empty($markerId) && $partnerId){
        $markerId = get_user_meta($partnerId ,"marker_id", true);
    }

	SIM\updateFamilyMeta( $userId, "marker_id", $markerId);
}

// Update marker title whenever there are changes to the family
add_action('sim_family_safe', __NAMESPACE__.'\onFamilySave');
function onFamilySave($userId){
    $maps       = new Maps();

	//Update the marker title
	$markerId   = get_user_meta($userId, "marker_id", true);

    $title      = SIM\getFamilyName($userId);
    
	$maps->updateMarkerTitle($markerId, $title);
}

//Update marker whenever the location changes
add_action('sim_location_update', __NAMESPACE__.'\locationUpdate', 10, 2);
function locationUpdate($userId, $location){
    global $wpdb;

    //Get any existing marker id from the db
    $markerId = get_user_meta($userId,"marker_id",true);
    if (is_numeric($markerId)){
        //Retrieve the marker icon id from the db
        $query = $wpdb->prepare("SELECT icon FROM {$wpdb->prefix}ums_markers WHERE id = %d ", $markerId);
        $markerIconId = $wpdb->get_var($query);
        
        //Set the marker_id to null if not found in the db
        if($markerIconId == null){
            $markerId = null;
        }
    }

    $maps   = new Maps();
    //Marker does not exist, create it
    if (!is_numeric($markerId)){			
        //Create a marker
        $maps->createUserMarker($userId, $location);
    //Marker needs an update
    }else{
        $maps->updateMarkerLocation($markerId, $location);
    }
}

// Remove marker when location is removed
add_action('sim_location_removal', __NAMESPACE__.'\locationRemoval');
function locationRemoval($userId){
	//Delete the marker as well
    $maps   = new Maps();
	$maps->removePersonalMarker($userId);
    delete_user_meta( $userId, 'marker_id');
}


// Update marker icon when family picture is changed
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 10, 3);
function beforeSavingFormData($formResults, $object){
	if($object->formData->name == 'profile_picture'){
        $privacyPreference = (array)get_user_meta( $object->userId, 'privacy_preference', true );
        $family				= (array)get_user_meta($object->userId, 'family', true);
        $maps               = new Maps();
        
        //update a marker icon only if privacy allows and no family picture is set
        if (empty($privacyPreference['hide_profile_picture']) && !is_numeric($family['picture'][0])){
            $markerId = get_user_meta($object->userId, "marker_id", true);
            
            //New profile picture is set, update the marker icon
            if(is_numeric(get_user_meta($object->userId, 'profile_picture', true))){
                $iconUrl = SIM\USERMANAGEMENT\getProfilePictureUrl($object->userId);
                
                //Save profile picture as icon
                $maps->createIcon($markerId, get_userdata($object->userId)->user_login, $iconUrl, 1);
            }else{
                //remove the icon
                $maps->removeIcon($markerId);
            }
        }
    }

    // Update marker when privacy options are changed
	if($object->formData->name == 'user_generics'){
        if(is_array($formResults['privacy_preference']) && in_array("hide_location", $formResults['privacy_preference'])){
            $markerId = get_user_meta($object->userId, "marker_id", true);

            if(is_numeric($markerId)){
                $maps = new Maps();
                $maps->removeMarker($markerId);
            }
        }
    }

	return $formResults;
}