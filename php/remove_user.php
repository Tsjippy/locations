<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('delete_user', __NAMESPACE__.'\deleteUser');
function deleteUser($userId){
	$maps			= new Maps();
	$family 		= new SIM\FAMILY\Family();
	$familyMembers	= $family->getFamily($userId, true);
	$count			= count($familyMembers);
	
	//Only remove if there is no family
	if ($count === 0){
		//Check if a personal marker exists for this user
		$markerId = get_user_meta($userId, "marker_id", true);

        $maps->removeMarker($markerId);
	//User has family
	}elseif($count == 1){
        //Get the partners display name to use as the new title
        $title = $familyMembers[0]->display_name ;

        //Update
        $markerId = get_user_meta($userId, "marker_id", true);
		$maps->updateMarkerTitle($markerId, $title);
    }
}