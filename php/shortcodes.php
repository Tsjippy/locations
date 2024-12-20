<?php
namespace SIM\LOCATIONS;
use SIM;

add_shortcode("markerdescription", __NAMESPACE__.'\markerDescription');
function markerDescription($atts){
    $a = shortcode_atts( array(
        'userid' => '',
    ), $atts );
    
    $userId = $a['userid'];
    
    if(is_numeric($userId)){
        wp_enqueue_style('sim_locations_style');
        
        $privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );

        $description = "";
        if (empty($privacyPreference['hide_profile_picture'])){
            $description .= SIM\displayProfilePicture($userId, [80,80], true, true);
        }
        
        //Add the post link to the marker content
        $url			 = SIM\maybeGetUserPageUrl($userId);
        $description	.= "<a href='$url' style='display:block;' class='page_link'>More info</a><br>";
        
        return $description;
    }
}

add_shortcode('ministry_description', __NAMESPACE__.'\ministryDescriptionShortcode');
function ministryDescriptionShortcode($atts){
    // check double posting
    $posts = get_posts(
        array(
            'post_type'              => 'location',
            'title'                  => $atts['name'],
            'post_status'            => 'publish',
            'numberposts'            => -1,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'orderby'                => 'post_date ID',
            'order'                  => 'ASC',
        )
    );

    if(empty($posts)){
        return '';
    }

    return getLocationEmployees($posts[0]);
}

add_shortcode("location_description", __NAMESPACE__.'\locationDescription');
function locationDescription($atts){
    $postId     = $atts['id'];
    if(!is_numeric($postId)){
        return '';
    }

    $location   = get_post_meta($postId, 'location', true);
    if(!is_array($location) && !empty($location)){
        $location  = json_decode($location, true);
    }
    $latitude   = $location['latitude'];
    $longitude  = $location['longitude'];

    //Add the profile picture to the marker content
    $postThumbnail = get_the_post_thumbnail($postId, 'thumbnail', array( 'class' => 'aligncenter markerpicture' , 'style' => 'max-height:100px;',));

    //Add the post excerpt to the marker content
    $description    = $postThumbnail;
    
    if(!isset($atts['basic'])){
        $description    .= wp_trim_words(wp_trim_excerpt("", $postId), 25);
    }

    //Add the post link to the marker content
    $url            = get_permalink($postId);

    if(get_the_ID() != $postId){
        $description    .= "<a href='$url' style='display:block;' class='page_link'>Show full descripion</a><br>";
    }

    $description    .= "<p><a class='button' onclick='getRoute(this, $latitude, $longitude)'>Get directions</a></p>";;
    
    return $description;
}