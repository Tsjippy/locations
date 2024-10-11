<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('sim-before-print-content', function($post, $pdf){
    if($post->post_type != 'location'){
        return;
    }
    
    $pdf->printImage(get_the_post_thumbnail_url($post), -1, 20, -1, -1, true, true);
		
    //Address
    $location   = get_post_meta(get_the_ID(), 'location', true);
    if(is_array($location) && !empty($location['address'])){
        $url = SIM\pathToUrl(MODULE_PATH.'pictures/location.png');
        $pdf->printImage($url, 10, -1, 10, 10);
        $pdf->write(10, $location['address']);
    }
    
    $tel = get_post_meta(get_the_ID(), 'tel', true);

    if(!empty($tel)){
        // tel
        $url = SIM\pathToUrl(MODULE_PATH.'pictures/tel.png');
        $pdf->printImage($url, 100, -1, 10, 10);
        $pdf->write(10, $tel);
    }
    
    //Url
    $imageUrl = SIM\pathToUrl(MODULE_PATH.'pictures/url.png');
    $y      = $pdf->getY()+12;
    $url    = get_post_meta(get_the_ID(), 'url', true);
    if(!empty($url)){
        $pdf->printImage($imageUrl, 10, $y, 10, 10);
        $pdf->write(10, $url);
    }

    $pdf->Ln(20);
    $pdf->writeHTML('<b>Description:</b>');
}, 10, 2);