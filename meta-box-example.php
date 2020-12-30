<?php
/*
Plugin Name: Meta Box Example
Description: Example demonstrating how to add Meta Boxes.
Plugin URI:  https://plugin-planet.com/
Author:      Jeff Starr
Version:     1.0
*/



// register meta box
function myplugin_add_meta_box() {

	$post_types = array( 'post', 'page' );

	foreach ( $post_types as $post_type ) {

		add_meta_box(
			'myplugin_meta_box',         // Unique ID of meta box
			'MyPlugin Meta Box',         // Title of meta box
			'myplugin_display_meta_box', // Callback function
			$post_type                   // Post type
		);

	}

}
add_action( 'add_meta_boxes', 'myplugin_add_meta_box' );


///////////////
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'custom-metabox', 'Select values', 'fill_metabox', 'post', 'normal' );
});
///////////////





// display meta box
function myplugin_display_meta_box( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'myplugin_meta_box_nonce' );

	
	$postmeta = get_post_meta( $post->ID, '_myplugin_meta_key2', true );
	// var_dump( $postmeta );
	
	$users = get_users( [ 'role__in' => [ 'author', 'editor', 'administrator' ] ] );

	foreach($users as $user) {
		if ( is_array( $postmeta ) && in_array( $user->ID, $postmeta ) ) {
            $checked = 'checked="checked"';
        } else {
            $checked = null;
        }
        ?>

        <p>
            <input  type="checkbox" name="myplugin-meta-box2[]" value="<?php echo $user->ID;?>" <?php echo $checked; ?> />
            <?php echo $user->user_login;?>
        </p>

        <?php
	}

}

////////////////////////
function fill_metabox( $post ) {
    wp_nonce_field( basename(__FILE__), 'mam_nonce' );

    // How to use 'get_post_meta()' for multiple checkboxes as array?
	$postmeta = maybe_unserialize( get_post_meta( $post->ID, 'elements', true ) );
	// var_dump($postmeta);

	// $users = get_users( [ 'role__in' => [ 'author', 'editor', 'administrator' ] ] );
	// // var_dump($users);
	// foreach($users as $user) {
	// 	var_dump($user->ID);
	// }

    // Our associative array here. id = value
    $elements = array(
        'apple'  => 'Apple',
        'orange' => 'Orange',
        'banana' => 'Banana'
    );

    // Loop through array and make a checkbox for each element
    foreach ( $elements as $id => $element) {

        // If the postmeta for checkboxes exist and 
        // this element is part of saved meta check it.
        if ( is_array( $postmeta ) && in_array( $id, $postmeta ) ) {
            $checked = 'checked="checked"';
        } else {
            $checked = null;
        }
        ?>

        <p>
            <input  type="checkbox" name="multval[]" value="<?php echo $id;?>" <?php echo $checked; ?> />
            <?php echo $element;?>
        </p>

        <?php
    }
}
///////////////////////////////



// save meta box
function myplugin_save_meta_box( $post_id ) {

	$is_autosave = wp_is_post_autosave( $post_id );
	$is_revision = wp_is_post_revision( $post_id );

	$is_valid_nonce = false;

	// echo '<pre>';
	// print_r( $_POST );
	// echo '</pre>';

	if ( isset( $_POST[ 'myplugin_meta_box_nonce' ] ) ) {

		if ( wp_verify_nonce( $_POST[ 'myplugin_meta_box_nonce' ], basename( __FILE__ ) ) ) {

			$is_valid_nonce = true;

		}

	}

	if ( $is_autosave || $is_revision || !$is_valid_nonce ) return;


	if(isset($_POST['myplugin-meta-box2'])) {
		update_post_meta(
			$post_id,                                            // Post ID
			'_myplugin_meta_key2',                                // Meta key
			$_POST[ 'myplugin-meta-box2' ] // Meta value
		);
	} else {
        delete_post_meta( $post_id, '_myplugin_meta_key2' );
    }


	// if ( array_key_exists( 'myplugin-meta-box2', $_POST ) ) {

	// 	update_post_meta(
	// 		$post_id,                                            // Post ID
	// 		'_myplugin_meta_key2',                                // Meta key
	// 		sanitize_text_field( $_POST[ 'myplugin-meta-box2' ] ) // Meta value
	// 	);

	// }

}
add_action( 'save_post', 'myplugin_save_meta_box' );


///////////////////////////////////////
add_action( 'save_post', function( $post_id ) {
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'mam_nonce' ] ) && wp_verify_nonce( $_POST[ 'mam_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }

    // If the checkbox was not empty, save it as array in post meta
    if ( ! empty( $_POST['multval'] ) ) {
        update_post_meta( $post_id, 'elements', $_POST['multval'] );

    // Otherwise just delete it if its blank value.
    } else {
        delete_post_meta( $post_id, 'elements' );
    }

});
////////////////////////////////////////////////


function filter_the_content_in_the_main_loop( $content ) {
 
    // Check if we're inside the main loop in a single Post.
    if ( is_singular() && in_the_loop() && is_main_query() ) {

        global $post;
        $postmeta = get_post_meta( $post->ID, '_myplugin_meta_key2', true );
        // var_dump( $postmeta );
		
		if(empty($postmeta)) {
			$users = [];
		} else {
			$users = get_users( [ 'include' => $postmeta ] );
		}
		// var_dump( $users );
		
        $content_add = '';
        foreach($users as $user) {
            // print_r($user->user_login);
            $content_add .= "<div><a href=" . $user->user_url . ">" . $user->user_login . "</a>";
            $content_add .= get_avatar( $user->ID, 26 ) . "</div>"; 

        }

        return $content . $content_add;
        // return $content . esc_html__( 'I’m filtering the content inside the main loop', 'wporg');
    }
 
    return $content;
}
add_filter( 'the_content', 'filter_the_content_in_the_main_loop', 99 );