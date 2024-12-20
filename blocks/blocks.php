<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('init',__NAMESPACE__.'\blockInit' );
function blockInit() {
	register_block_type(
		__DIR__ . '/metadata/build',
		array(
			"attributes"	=>  [
				"lock"	=> [
					"type"	=> "object",
					"default"	=> [
						"move"		=> true,
						"remove"	=> true
					]
				],
				'tel'	=> [
					'type'	=> 'string',
					'default'	=> ''
				],
				'url'	=> [
					'type'	=> 'string',
					'default'	=> ''
				],
				'location'	=> [
					'type'	=> 'string',
					'default'	=> '{"address":""}'
				],
			]
		)
	);

	// register custom meta tag field
	register_post_meta( 'location', 'tel', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'location', 'url', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'location', 'location', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
}

add_action( 'enqueue_block_assets', __NAMESPACE__.'\loadBlockAssets' );
function loadBlockAssets($tes){
	if ( is_admin() ) {
		addGoogleMapsApiKey();
	}
}