<?php

/**
 * Enqueue scripts and styles.
 *
 * @since Method7 1.0
 */
function method7_scripts() {

	// Load our main stylesheet.
	wp_enqueue_style( 'bootstrap-style', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css' );
	wp_enqueue_style( 'method7-style-dist', get_template_directory_uri() . '/dist/style.css');
	wp_enqueue_style( 'method7-style', get_template_directory_uri() . '/style.css' );

    // Load scripts
	//wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-3.2.1.slim.min.js', '20171006', false );	
	wp_enqueue_script( 'scrollmagic', 'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.5/ScrollMagic.min.js' , array( 'jquery' ), '1.0', false );    
	//wp_enqueue_script( 'popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js', array( 'jquery' ), '20171006', false );
    //wp_enqueue_script( 'bootstrap-script', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js', array( 'jquery' ), '20171006', false );
    
    wp_enqueue_script( 'method7-script', get_template_directory_uri() . '/dist/app.js' , array(), '1.0', true );

	$url = trailingslashit( home_url() );
	$path = trailingslashit( parse_url( $url, PHP_URL_PATH ) );

	wp_scripts()->add_data( 'method7-script', 'data', sprintf( 'var Method7Settings = %s;', wp_json_encode( array(
		'title' => get_bloginfo( 'name', 'display' ),
		'path' => $path,
		'URL' => array(
			'api' => esc_url_raw( get_rest_url( null, '/wp/v2/' ) ),
			'root' => esc_url_raw( $url ),
		),
		'woo' => array(
			'url' => esc_url_raw( 'https://pure-naturals.local/wp-json/wc/v2/' ), // hard-code URL since it needs to be HTTPS for WC REST API to work
			'consumer_key' => 'ck_159bf60aa3f3e71ff2004eb9b59d47427464fc24',
			'consumer_secret' => 'cs_81f53bb13f4ff25b85521e81ec8a051165db8226'
		),
	) ) ) );
}
add_action( 'wp_enqueue_scripts', 'method7_scripts' );

// Add various fields to the JSON output
function method7_register_fields() {
	// Add Author Name
	register_rest_field( 'post',
		'author_name',
		array(
			'get_callback'		=> 'method7_get_author_name',
			'update_callback'	=> null,
			'schema'			=> null
		)
	);
	// Add Featured Image
	register_rest_field( 'post',
		'featured_image_src',
		array(
			'get_callback'		=> 'method7_get_image_src',
			'update_callback'	=> null,
			'schema'			=> null
		)
    );
    // Add Published Date
	register_rest_field( 'post',
        'published_date',
        array(
            'get_callback'		=> 'method7_published_date',
            'update_callback'	=> null,
            'schema'			=> null
        )
	);
}
add_action( 'rest_api_init', 'method7_register_fields' );

function method7_get_author_name( $object, $field_name, $request ) {
	return get_the_author_meta( 'display_name' );
}
function method7_get_image_src( $object, $field_name, $request ) {
    if($object[ 'featured_media' ] == 0) {
        return $object[ 'featured_media' ];
    }
	$feat_img_array = wp_get_attachment_image_src( $object[ 'featured_media' ], 'thumbnail', true );
    return $feat_img_array[0];
}
function method7_published_date( $object, $field_name, $request ) {
	return get_the_time('F j, Y');
}

function method7_excerpt_length( $length ) {
    return 20;
}
add_filter( 'excerpt_length', 'method7_excerpt_length' );

/**
 * Add Theme Support
 * 
 * @see https://developer.wordpress.org/reference/functions/add_theme_support/
 */
add_theme_support( 'post-thumbnails' );

/**
 * Simple helper to debug to the console
 *
 * @param $data object, array, string $data
 * @param $context string  Optional a description.
 *
 * @return string
 */
function debug_to_console($data, $context = 'Debug in Console') {

    // Buffering to solve problems frameworks, like header() in this and not a solid return.
    ob_start();

    $output  = 'console.info(\'' . $context . ':\');';
    $output .= 'console.log(' . json_encode($data) . ');';
    $output  = sprintf('<script>%s</script>', $output);

    echo $output;
}