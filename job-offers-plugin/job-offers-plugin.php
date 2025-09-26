<?php
/*
Plugin Name: Job Offers Plugin
Description: A custom plugin to create and manage job offers with REST API integration.
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Custom Post Type for Job Offers
function register_job_offer_post_type() {
    $labels = array(
        'name'                  => _x('Job Offers', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('Job Offer', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('Job Offers', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('Job Offer', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Add New', 'textdomain'),
        'add_new_item'          => __('Add New Job Offer', 'textdomain'),
        'new_item'              => __('New Job Offer', 'textdomain'),
        'edit_item'             => __('Edit Job Offer', 'textdomain'),
        'view_item'             => __('View Job Offer', 'textdomain'),
        'all_items'             => __('All Job Offers', 'textdomain'),
        'search_items'          => __('Search Job Offers', 'textdomain'),
        'not_found'             => __('No job offers found.', 'textdomain'),
        'not_found_in_trash'    => __('No job offers found in Trash.', 'textdomain'),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'job-offer'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => 5,
        'show_in_rest'          => true,
        'supports'              => array('title', 'editor', 'custom-fields'),
    );

    register_post_type('job_offer', $args);
}
add_action('init', 'register_job_offer_post_type');

// Register Custom Fields for Job Offers
function add_job_offer_meta_boxes() {
    add_meta_box(
        'job_offer_meta_box',
        'Job Offer Details',
        'display_job_offer_meta_box',
        'job_offer',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_job_offer_meta_boxes');

function display_job_offer_meta_box($post) {
    $job_location = get_post_meta($post->ID, 'job_location', true);
    $job_type = get_post_meta($post->ID, 'job_type', true);

    echo '<label for="job_location">Location</label>';
    echo '<input type="text" id="job_location" name="job_location" value="' . esc_attr($job_location) . '" size="25" />';
    
    echo '<br><br>';

    echo '<label for="job_type">Job Type</label>';
    echo '<input type="text" id="job_type" name="job_type" value="' . esc_attr($job_type) . '" size="25" />';
    
    // Add nonce field for security
    wp_nonce_field('save_job_offer_meta_box_data', 'job_offer_meta_box_nonce');
}

function save_job_offer_meta_box_data($post_id) {
    // Verify nonce
    if (!isset($_POST['job_offer_meta_box_nonce']) || !wp_verify_nonce($_POST['job_offer_meta_box_nonce'], 'save_job_offer_meta_box_data')) {
        return;
    }

    // Check if the current user has permission to save data
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Sanitize and save the custom fields
    if (isset($_POST['job_location'])) {
        update_post_meta($post_id, 'job_location', sanitize_text_field($_POST['job_location']));
    }

    if (isset($_POST['job_type'])) {
        update_post_meta($post_id, 'job_type', sanitize_text_field($_POST['job_type']));
    }
}
add_action('save_post', 'save_job_offer_meta_box_data');
