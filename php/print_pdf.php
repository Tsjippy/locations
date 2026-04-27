<?php
namespace TSJIPPY\LOCATIONS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('tsjippy-before-print-content', __NAMESPACE__.'\beforePrint', 10, 2);
function beforePrint($post, $pdf){
    if($post->post_type != 'location'){
        return;
    }
    
    $pdf->printImage(get_the_post_thumbnail_url($post), -1, 20, -1, -1, true, true);
		
    //Address
    $location   = get_post_meta(get_the_ID(), 'location', true);
    if(is_array($location) && !empty($location['address'])){
        $url = TSJIPPY\pathToUrl(PLUGINPATH.'pictures/location.png');
        $pdf->printImage($url, 10, -1, 10, 10);
        $pdf->write(10, $location['address']);
    }
    
    $tel = get_post_meta(get_the_ID(), 'tel', true);

    if(!empty($tel)){
        // tel
        $url = TSJIPPY\pathToUrl(PLUGINPATH.'pictures/tel.png');
        $pdf->printImage($url, 100, -1, 10, 10);
        $pdf->write(10, $tel);
    }
    
    //Url
    $imageUrl = TSJIPPY\pathToUrl(PLUGINPATH.'pictures/url.png');
    $y      = $pdf->getY()+12;
    $url    = get_post_meta(get_the_ID(), 'url', true);
    if(!empty($url)){
        $pdf->printImage($imageUrl, 10, $y, 10, 10);
        $pdf->write(10, $url);
    }

    $pdf->Ln(20);
    $pdf->writeHTML('<b>Description:</b>');
}