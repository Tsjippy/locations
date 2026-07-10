<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

add_action('init', __NAMESPACE__ . '\blockInit');
function blockInit()
{
    register_block_type(
        'tsjippy-locations/description',
        array(
            'title'           => __( 'Location Description', 'tsjippy' ),
            'attributes'      => array(
                'name'   => array(
                    'label'   => __( 'Location Name', 'tsjippy' ),
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => __NAMESPACE__.'\locationBlock',
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'location'
        )
    );

    register_block_type(
        __DIR__ . '/metadata/build',
        array(
            "attributes"    =>  [
                "lock"    => [
                    "type"    => "object",
                    "default"    => [
                        "move"        => true,
                        "remove"    => true
                    ]
                ],
                'tel'    => [
                    'type'    => 'string',
                    'default'    => ''
                ],
                'url'    => [
                    'type'    => 'string',
                    'default'    => ''
                ],
                'location'    => [
                    'type'    => 'string',
                    'default'    => '{"address":""}'
                ],
            ]
        )
    );

    // register custom meta tag field
    register_post_meta('location', "tsjippy_tel", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_post_meta('location', "tsjippy_url", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));

    register_post_meta('location', "tsjippy_location", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}

add_action('enqueue_block_assets', __NAMESPACE__ . '\loadBlockAssets');
function loadBlockAssets($tes)
{
    if (is_admin()) {
        addGoogleMapsApiKey();
    }
}

/**
 * Renders the description of a location
 * 
 * @param   array   $atts   The block attributes
 */
function locationBlock($atts)
{
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

    if (empty($posts)) {
        return '';
    }

    return getLocationEmployees($posts[0], false);
}