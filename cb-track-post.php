<?php
/**
 * Plugin Name: CB Track Post
 * Description: Tracks user's viewed posts and highlights them in category/archive pages
 * Version: 1.0.0
 * Author: Chinmoy Biswas
 */

if (!defined('ABSPATH')) {
    exit;
}

function cb_enqueue_scripts()
{
    // Enqueue a custom JavaScript file
    wp_enqueue_script('cb-track-post', plugin_dir_url(__FILE__) . 'assets/cb-track-post.js', array('jquery'), '1.0.0', true);

}
add_action('wp_enqueue_scripts', 'cb_enqueue_scripts');

//check if single page, then var dump the post id
function cb_track_post()
{
    if (is_single()) {

        $post_id = get_the_ID();
        // Check if the post ID is set
        if ($post_id) {
            // Store the post ID in a cookie
            setcookie('cb_post_id', $post_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}
add_action('wp_head', 'cb_track_post');
