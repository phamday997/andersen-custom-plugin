<?php
/**
 * Plugin Name: Andersen - Team Member Details Saver
 * Description: Save "Function" and "Email" fields for the Team Member post type.
 * Version: 1.0.0
 * Author: Pham van Day
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Save meta fields "function" and "email" for team_member post type.
 *
 * This plugin relies on the existing meta box that outputs:
 *  - input name="function"
 *  - input name="email"
 * and the nonce:
 *  - wp_nonce_field( 'save_team_member_meta_box_data', 'team_member_meta_box_nonce' );
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 */
function atd_save_team_member_details_main_meta_box( $post_id, $post, $update ) {

	// Only handle team_member post type.
	if ( 'team_member' !== $post->post_type ) {
		return;
	}

	// Check nonce from the existing meta box.
	if (
		! isset( $_POST['team_member_meta_box_nonce'] )
		|| ! wp_verify_nonce( $_POST['team_member_meta_box_nonce'], 'save_team_member_meta_box_data' )
	) {
		return;
	}

	// Stop during autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Permission check.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save "Function".
	if ( isset( $_POST['function'] ) ) {
		update_post_meta(
			$post_id,
			'function',
			sanitize_text_field( wp_unslash( $_POST['function'] ) )
		);
	}

	// Save "Email".
	if ( isset( $_POST['email'] ) ) {
		update_post_meta(
			$post_id,
			'email',
			sanitize_email( wp_unslash( $_POST['email'] ) )
		);
	}
}
add_action( 'save_post_team_member', 'atd_save_team_member_details_main_meta_box', 10, 3 );
