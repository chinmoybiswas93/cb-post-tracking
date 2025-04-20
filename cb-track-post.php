<?php
/**
 * Plugin Name: CB Track Post
 * Description: Tracks user's viewed posts and highlights them in category/archive pages
 * Version: 1.0.1
 * Author: Chinmoy Biswas
 */

if (!defined('ABSPATH')) {
    exit;
}

function cb_enqueue_scripts()
{
    // Enqueue a custom JavaScript file
    wp_enqueue_script('cb-track-post', plugin_dir_url(__FILE__) . 'assets/cb-track-post.js', array('jquery'), '1.0.1', true);

    // Enqueue a custom CSS file
    wp_enqueue_style('cb-track-post', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.1');
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


//add a bookmark in the post single page rounded div>button>heart-icon
function cb_add_bookmark_button()
{
    if (is_single()) {
        $post_id = get_the_ID();
        $is_bookmarked = false;

        // Check if bookmarked_posts cookie exists
        if (isset($_COOKIE['bookmarked_posts'])) {
            $bookmarked_posts = json_decode(stripslashes($_COOKIE['bookmarked_posts']), true);
            $is_bookmarked = in_array($post_id, $bookmarked_posts);
        }

        echo '<div class="cb-bookmark-button">
            <button class="cb-bookmark-btn ' . ($is_bookmarked ? 'bookmarked' : '') . '">
                <img src="' . plugin_dir_url(__FILE__) . 'assets/heart-icon.png" 
                     alt="Bookmark" 
                     style="width: 24px; height: 24px; max-width: unset;">
            </button>
        </div>';
    }
}
add_action('wp_footer', 'cb_add_bookmark_button');



// create a shortcode to display the bookmarked posts form the cookie
function cb_display_bookmarked_posts()
{
    $output = '<div class="cb-bookmarked-posts">';
    if (isset($_COOKIE['bookmarked_posts'])) {
        $bookmarked_posts = json_decode(stripslashes($_COOKIE['bookmarked_posts']), true);

        if (!empty($bookmarked_posts)) {
            // Group posts by category
            $categorized_posts = array();

            foreach ($bookmarked_posts as $post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $categories = get_the_category($post_id);
                    if (!empty($categories)) {
                        $category_name = $categories[0]->name;
                        if (!isset($categorized_posts[$category_name])) {
                            $categorized_posts[$category_name] = array();
                        }
                        $categorized_posts[$category_name][] = array(
                            'id' => $post_id,
                            'title' => get_the_title($post_id),
                            'chapter' => get_post_meta($post_id, 'chapter_number', true) ?: '1'
                        );
                    }
                }
            }

            if (!empty($categorized_posts)) {
                foreach ($categorized_posts as $category => $posts) {
                    $output .= '<div class="bookmark-category">';
                    foreach ($posts as $post) {
                        $output .= '<div class="bookmark-item">';
                        $output .= '<a href="' . get_permalink($post['id']) . '">';
                        $output .= '<strong>' . esc_html($category) . '</strong><br>';
                        $output .= '<span class="chapter">Chapter: ' . esc_html($post['title']) . '</span>';
                        $output .= '</a>';
                        $output .= '</div>';
                    }
                    $output .= '</div>';
                }
            } else {
                $output .= '<p>No bookmarked posts found.</p>';
            }
        } else {
            $output .= '<p>No bookmarked posts found.</p>';
        }
    } else {
        $output .= '<p>No bookmarked posts found.</p>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('cb_bookmarked_posts', 'cb_display_bookmarked_posts');