<?php
/*
Plugin Name: Andersen Search
Description: Permet de faire fonctionner la barre du site internet global andersen
Version: 1.0
*/

add_action('rest_api_init', function () {
    register_rest_route('andersen/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'andersen_search_content',
        'permission_callback' => '__return_true'
    ));
});

function andersen_search_content($request) {
    $search_term = $request->get_param('q');
    
    // Rechercher les articles
    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        's' => $search_term,
        'posts_per_page' => 10
    ));

    // Rechercher les utilisateurs
    $users = get_users(array(
        'search' => "*{$search_term}*",
        'number' => 10
    ));

    // Formater les résultats
    $formatted_posts = array_map(function($post) {
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'url' => get_permalink($post->ID),
            'type' => 'post'
        );
    }, $posts);

    $formatted_users = array_map(function($user) {
        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'url' => get_author_posts_url($user->ID),
            'type' => 'user'
        );
    }, $users);

    // Combiner et renvoyer les résultats
    return array(
        'posts' => $formatted_posts,
        'users' => $formatted_users
    );
}
