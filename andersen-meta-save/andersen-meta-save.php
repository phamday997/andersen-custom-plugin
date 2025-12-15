<?php
/**
 * Plugin Name: Andersen - Meta Saver (Fix)
 * Description: Centralize and fix saving logic for custom meta boxes (team_member, expertise, business_pack, post, job_offer).
 * Version: 1.0.0
 * Author: Andersen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Andersen_Meta_Saver_Fix {

	public static function init(): void {
		// Team Member.
		add_action( 'save_post_team_member', [ __CLASS__, 'save_team_member_main' ], 10, 3 );
		add_action( 'save_post_team_member', [ __CLASS__, 'save_team_member_extra' ], 10, 3 );
		add_action( 'save_post_team_member', [ __CLASS__, 'save_team_member_script_preselect' ], 10, 3 );
		add_action( 'save_post_team_member', [ __CLASS__, 'save_team_member_expertises' ], 10, 3 );

		// Post.
		add_action( 'save_post_post', [ __CLASS__, 'save_post_selected_team_members' ], 10, 3 );
		add_action( 'save_post_post', [ __CLASS__, 'save_post_selected_expertises' ], 10, 3 );
		add_action( 'save_post_post', [ __CLASS__, 'save_post_intro' ], 10, 3 );
		add_action( 'save_post_post', [ __CLASS__, 'save_post_cta' ], 10, 3 );

		// Expertise.
		add_action( 'save_post_expertise', [ __CLASS__, 'save_expertise_fields' ], 10, 3 );

		// Business Pack.
		add_action( 'save_post_business_pack', [ __CLASS__, 'save_business_pack_fields' ], 10, 3 );

		// Job Offer.
		add_action( 'add_meta_boxes_job_offer', [ __CLASS__, 'register_job_offer_intro_metabox' ] );
		add_action( 'save_post_job_offer', [ __CLASS__, 'save_job_offer_intro' ], 10, 3 );
	}

	// -------------------------
	// Helpers
	// -------------------------

	private static function should_skip_save( int $post_id, \WP_Post $post ): bool {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return true;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return true;
		}
		// On REST saves, $_POST may be empty; we still allow if nonce is not required for that flow.
		return false;
	}

	private static function verify_nonce( string $field, string $action ): bool {
		if ( empty( $_POST[ $field ] ) ) {
			return false;
		}
		return (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action );
	}

	private static function post_field( string $key ): ?string {
		if ( ! isset( $_POST[ $key ] ) ) {
			return null;
		}
		return wp_unslash( $_POST[ $key ] );
	}

	// -------------------------
	// TEAM MEMBER
	// -------------------------

	public static function save_team_member_main( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// Your metabox nonce:
		// wp_nonce_field( 'save_team_member_meta_box_data', 'team_member_meta_box_nonce' );
		if ( ! self::verify_nonce( 'team_member_meta_box_nonce', 'save_team_member_meta_box_data' ) ) {
			return;
		}

		$function = self::post_field( 'function' );
		if ( null !== $function ) {
			update_post_meta( $post_id, 'function', sanitize_text_field( $function ) );
		}

		$email = self::post_field( 'email' );
		if ( null !== $email ) {
			update_post_meta( $post_id, 'email', sanitize_email( $email ) );
		}
	}

	public static function save_team_member_extra( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// Your extra details metabox nonce:
		// wp_nonce_field('save_team_member_details_meta_box_data', 'team_member_details_meta_box_nonce');
		if ( ! self::verify_nonce( 'team_member_details_meta_box_nonce', 'save_team_member_details_meta_box_data' ) ) {
			return;
		}

		$text_fields = [
			'phone',
			'languages',
			'linkedin',
			'fax',
			'subtitle',
		];

		$textarea_fields = [
			'qualifications',
			'character_traits',
			'affiliations',
			'conferences',
		];

		foreach ( $text_fields as $key ) {
			$val = self::post_field( $key );
			if ( null !== $val ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $val ) );
			}
		}

		foreach ( $textarea_fields as $key ) {
			$val = self::post_field( $key );
			if ( null !== $val ) {
				update_post_meta( $post_id, $key, sanitize_textarea_field( $val ) );
			}
		}
	}

	public static function save_team_member_script_preselect( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// wp_nonce_field('save_script_preselect_meta_box_data_for_team_members', 'script_preselect_meta_box_nonce');
		if ( ! self::verify_nonce( 'script_preselect_meta_box_nonce', 'save_script_preselect_meta_box_data_for_team_members' ) ) {
			return;
		}

		$val = self::post_field( 'script_preselect' );
		if ( null !== $val ) {
			update_post_meta( $post_id, 'script_preselect', sanitize_text_field( $val ) );
		}
	}

	public static function save_team_member_expertises( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// Your team_member expertise box uses:
		// wp_nonce_field('save_expertise_meta_box_data_for_team_members', 'expertise_meta_box_nonce');
		if ( ! self::verify_nonce( 'expertise_meta_box_nonce', 'save_expertise_meta_box_data_for_team_members' ) ) {
			return;
		}

		if ( isset( $_POST['expertise_select'] ) && is_array( $_POST['expertise_select'] ) ) {
			$ids = array_map( 'intval', wp_unslash( $_POST['expertise_select'] ) );
			update_post_meta( $post_id, '_selected_expertises', $ids );
		} else {
			delete_post_meta( $post_id, '_selected_expertises' );
		}
	}

	// -------------------------
	// POST
	// -------------------------

	public static function save_post_selected_team_members( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// wp_nonce_field('save_team_members_nonce', 'team_members_nonce_field');
		if ( ! self::verify_nonce( 'team_members_nonce_field', 'save_team_members_nonce' ) ) {
			return;
		}

		if ( isset( $_POST['team_member_select'] ) && is_array( $_POST['team_member_select'] ) ) {
			$ids = array_map( 'intval', wp_unslash( $_POST['team_member_select'] ) );
			update_post_meta( $post_id, '_selected_team_members', $ids );
		} else {
			delete_post_meta( $post_id, '_selected_team_members' );
		}
	}

	public static function save_post_selected_expertises( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// wp_nonce_field('save_expertise_meta_box_data', 'expertise_meta_box_nonce');
		if ( ! self::verify_nonce( 'expertise_meta_box_nonce', 'save_expertise_meta_box_data' ) ) {
			return;
		}

		if ( isset( $_POST['expertise_select'] ) && is_array( $_POST['expertise_select'] ) ) {
			$ids = array_map( 'intval', wp_unslash( $_POST['expertise_select'] ) );
			update_post_meta( $post_id, '_selected_expertises', $ids );
			// If you want taxonomy assignment too:
			wp_set_object_terms( $post_id, $ids, 'expertise' );
		} else {
			delete_post_meta( $post_id, '_selected_expertises' );
			wp_set_object_terms( $post_id, [], 'expertise' );
		}
	}

	public static function save_post_intro( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// wp_nonce_field('save_intro_meta_box_data', 'intro_meta_box_nonce');
		if ( ! self::verify_nonce( 'intro_meta_box_nonce', 'save_intro_meta_box_data' ) ) {
			return;
		}

		$intro = self::post_field( 'intro' );
		if ( null !== $intro ) {
			update_post_meta( $post_id, 'intro', sanitize_textarea_field( $intro ) );
		}
	}

	public static function save_post_cta( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// wp_nonce_field('save_cta_meta_box_data', 'cta_meta_box_nonce');
		if ( ! self::verify_nonce( 'cta_meta_box_nonce', 'save_cta_meta_box_data' ) ) {
			return;
		}

		$cta = self::post_field( 'cta_text' );
		if ( null !== $cta ) {
			update_post_meta( $post_id, 'cta_text', sanitize_textarea_field( $cta ) );
		}
	}

	// -------------------------
	// EXPERTISE
	// -------------------------

	public static function save_expertise_fields( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		// Save short_description (textarea)
		if ( self::verify_nonce( 'expertise_short_description_nonce', 'save_expertise_short_description' ) ) {
			$val = self::post_field( 'short_description' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'short_description', sanitize_textarea_field( $val ) );
			}
		}

		// Save description (textarea)
		if ( self::verify_nonce( 'expertise_description_nonce', 'save_expertise_description' ) ) {
			$val = self::post_field( 'description' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'description', sanitize_textarea_field( $val ) );
			}
		}

		// Save display order
		if ( self::verify_nonce( 'expertise_display_order_nonce', 'save_expertise_display_order_data' ) ) {
			$val = self::post_field( 'expertise_display_order' );
			if ( null !== $val ) {
				$val = sanitize_text_field( $val );
				if ( $val === '' || is_numeric( $val ) ) {
					update_post_meta( $post_id, 'expertise_display_order', $val );
				}
			}
		}

		// Save SVG icon URL
		if ( self::verify_nonce( 'expertise_svg_icone_nonce', 'save_expertise_svg_icone_meta_box_data' ) ) {
			$val = self::post_field( 'expertise_svg_icone' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'expertise_svg_icone', esc_url_raw( $val ) );
			}
		}

		// Save background image URL
		if ( self::verify_nonce( 'expertise_background_image_nonce', 'save_expertise_background_image_meta_box_data' ) ) {
			$val = self::post_field( 'expertise_background_image' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'expertise_background_image', esc_url_raw( $val ) );
			}
		}
	}

	// -------------------------
	// BUSINESS PACK (FIX: no "all nonces required")
	// -------------------------

	public static function save_business_pack_fields( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		$did_any = false;

		if ( self::verify_nonce( 'business_pack_short_description_nonce', 'save_business_pack_short_description' ) ) {
			$val = self::post_field( 'short_description' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'short_description', sanitize_textarea_field( $val ) );
			}
			$did_any = true;
		}

		if ( self::verify_nonce( 'business_pack_benefit_1_nonce', 'save_business_pack_benefit_1' ) ) {
			$val = self::post_field( 'benefit_1' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'benefit_1', sanitize_textarea_field( $val ) );
			}
			$did_any = true;
		}

		if ( self::verify_nonce( 'business_pack_benefit_2_nonce', 'save_business_pack_benefit_2' ) ) {
			$val = self::post_field( 'benefit_2' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'benefit_2', sanitize_textarea_field( $val ) );
			}
			$did_any = true;
		}

		if ( self::verify_nonce( 'business_pack_benefit_3_nonce', 'save_business_pack_benefit_3' ) ) {
			$val = self::post_field( 'benefit_3' );
			if ( null !== $val ) {
				update_post_meta( $post_id, 'benefit_3', sanitize_textarea_field( $val ) );
			}
			$did_any = true;
		}

		// If no nonce matched, do nothing (avoid breaking other save flows).
		if ( ! $did_any ) {
			return;
		}
	}

	// -------------------------
	// JOB OFFER (FIX: add nonce + proper checks)
	// -------------------------

	public static function register_job_offer_intro_metabox(): void {
		add_meta_box(
			'job_offer_intro_fixed',
			'Introduction (Fixed)',
			[ __CLASS__, 'render_job_offer_intro_metabox' ],
			'job_offer',
			'normal',
			'default'
		);
	}

	public static function render_job_offer_intro_metabox( \WP_Post $post ): void {
		$intro = get_post_meta( $post->ID, '_job_offer_intro', true );
		wp_nonce_field( 'save_job_offer_intro_fixed', 'job_offer_intro_fixed_nonce' );
		echo '<textarea style="width:100%" rows="5" name="job_offer_intro">' . esc_textarea( (string) $intro ) . '</textarea>';
		echo '<p class="description">This metabox adds nonce + safe saving.</p>';
	}

	public static function save_job_offer_intro( int $post_id, \WP_Post $post, bool $update ): void {
		if ( self::should_skip_save( $post_id, $post ) ) {
			return;
		}

		if ( ! self::verify_nonce( 'job_offer_intro_fixed_nonce', 'save_job_offer_intro_fixed' ) ) {
			return;
		}

		$intro = self::post_field( 'job_offer_intro' );
		if ( null !== $intro ) {
			update_post_meta( $post_id, '_job_offer_intro', sanitize_textarea_field( $intro ) );
		}
	}
}

Andersen_Meta_Saver_Fix::init();
