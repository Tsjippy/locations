<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Update marker icon when family picture is updated
add_action('tsjippy-update-family-picture', __NAMESPACE__ . '\familiPicture', 10, 2);
function familiPicture($userId, $attachmentId)
{
    $family        = new TSJIPPY\FAMILY\Family();
    $maps       = new Maps();
    $markerId     = get_user_meta($userId, "tsjippy_marker_id", true);
    $url        = wp_get_attachment_url($attachmentId);
    $iconTitle    = $family->getFamilyName($userId);

    $maps->createIcon($markerId, $iconTitle, $url, 1);
}

// Update marker title whenever there are changes to the family
add_action('tsjippy-family-safe', __NAMESPACE__ . '\onFamilySave');
function onFamilySave($userId)
{
    $family        = new TSJIPPY\FAMILY\Family();
    $maps       = new Maps();

    //Update the marker title
    $markerId   = get_user_meta($userId, "tsjippy_marker_id", true);

    $title      = $family->getFamilyName($userId);

    $maps->updateMarkerTitle($markerId, $title);
}

//Update marker whenever the location changes
add_action('tsjippy-user-management-location-update', __NAMESPACE__ . '\locationUpdate', 10, 2);
function locationUpdate($userId, $location)
{
    global $wpdb;

    //Get any existing marker id from the db
    $markerId = get_user_meta($userId, "tsjippy_marker_id", true);
    if (is_numeric($markerId)) {
        //Retrieve the marker icon id from the db
        $markerIconId = TSJIPPY\getFromDb(
            "get_marker_icon_$markerId",
            "locations",
            "SELECT icon FROM %i WHERE id = %d LIMIT 1",
            "{$wpdb->prefix}ums_markers",
            $markerId
        );

        //Set the marker_id to null if not found in the db
        if ($markerIconId == null) {
            $markerId = null;
        }
    }

    $maps   = new Maps();
    //Marker does not exist, create it
    if (!is_numeric($markerId)) {
        //Create a marker
        $maps->createUserMarker($userId, $location);
        //Marker needs an update
    } else {
        $maps->updateMarkerLocation($markerId, $location);
    }
}

// Remove marker when location is removed
add_action('tsjippy-user-management-location-removal', __NAMESPACE__ . '\locationRemoval');
function locationRemoval($userId)
{
    //Delete the marker as well
    $maps   = new Maps();
    $maps->removePersonalMarker($userId);
    delete_user_meta($userId, 'tsjippy_marker_id');
}


// Update marker icon when family picture is changed
add_filter('tsjippy-before-inserting-formdata', __NAMESPACE__ . '\beforeSavingFormData', 10, 2);
function beforeSavingFormData($submission, $object)
{
    if ($object->formData->slug == 'profile_picture') {
        $privacyPreference  = (array)get_user_meta($object->userId, 'tsjippy_privacy_preference', true);
        $family                = new TSJIPPY\FAMILY\Family();
        $picture            = $family->getFamilyMeta($object->userId, 'family_picture', true);
        $maps               = new Maps();

        //update a marker icon only if privacy allows and no family picture is set
        if (empty($privacyPreference['hide_profile_picture']) && !is_numeric($picture)) {
            $markerId = get_user_meta($object->userId, "tsjippy_marker_id", true);

            //New profile picture is set, update the marker icon
            if (is_numeric(get_user_meta($object->userId, 'tsjippy_profile_picture', true))) {
                $iconUrl = TSJIPPY\USERMANAGEMENT\getProfilePictureUrl($object->userId);

                //Save profile picture as icon
                $maps->createIcon($markerId, get_userdata($object->userId)->user_login, $iconUrl, 1);
            } else {
                //remove the icon
                $maps->removeIcon($markerId);
            }
        }
    }

    // Update marker when privacy options are changed
    if ($object->formData->slug == 'user_generics') {
        if (is_array($submission->privacy_preference) && in_array("hide_location", $submission->privacy_preference)) {
            $markerId = get_user_meta($object->userId, "tsjippy_marker_id", true);

            if (is_numeric($markerId)) {
                $maps = new Maps();
                $maps->removeMarker($markerId);
            }
        }
    }

    return $submission;
}
