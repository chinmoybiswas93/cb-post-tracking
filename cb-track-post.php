<?php
/**
 * Plugin Name: CB Track Post
 * Description: Tracks user's viewed posts and highlights them in category/archive pages, with a bookmark button on single post pages.
 * Version: 1.0.2
 * Author: Chinmoy Biswas
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Track_Post
{
    private static $instance = null;
    private const VERSION = '1.0.2';
    private const COOKIE_EXPIRY = 86400 * 30; // 30 days

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_head', [$this, 'track_post']);
        add_action('wp_footer', [$this, 'add_bookmark_button']);
        add_shortcode('cb_bookmarked_posts', [$this, 'display_bookmarked_posts']);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'cb-track-post',
            plugin_dir_url(__FILE__) . 'assets/cb-track-post.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_enqueue_style(
            'cb-track-post',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            self::VERSION
        );
    }

    public function track_post()
    {
        if (!is_single()) {
            return;
        }

        $post_id = get_the_ID();
        if ($post_id) {
            setcookie('cb_post_id', $post_id, time() + self::COOKIE_EXPIRY, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    public function add_bookmark_button()
    {
        if (!is_single()) {
            return;
        }

        $post_id = get_the_ID();
        $is_bookmarked = $this->is_post_bookmarked($post_id);
        $empty_heart = plugin_dir_url(__FILE__) . 'assets/heart-empty.svg';
        $filled_heart = plugin_dir_url(__FILE__) . 'assets/heart-fill.svg';

        printf(
            '<div class="cb-bookmark-button">
                <button class="cb-bookmark-btn%s">
                    <img src="%s" class="heart-empty" alt="Add bookmark" style="width: 24px; height: 24px; max-width: unset;">
                    <img src="%s" class="heart-fill" alt="Remove bookmark" style="width: 24px; height: 24px; max-width: unset;">
                </button>
            </div>',
            $is_bookmarked ? ' bookmarked' : '',
            esc_url($empty_heart),
            esc_url($filled_heart)
        );
    }

    private function is_post_bookmarked($post_id)
    {
        if (!isset($_COOKIE['cb_bookmarked_posts'])) {
            return false;
        }

        $bookmarked_posts = json_decode(stripslashes($_COOKIE['cb_bookmarked_posts']), true);
        return is_array($bookmarked_posts) && in_array($post_id, $bookmarked_posts);
    }

    public function display_bookmarked_posts()
    {
        if (!isset($_COOKIE['cb_bookmarked_posts'])) {
            return $this->get_empty_bookmarks_message();
        }

        $bookmarked_posts = json_decode(stripslashes($_COOKIE['cb_bookmarked_posts']), true);
        if (empty($bookmarked_posts)) {
            return $this->get_empty_bookmarks_message();
        }

        $categorized_posts = $this->categorize_posts($bookmarked_posts);
        if (empty($categorized_posts)) {
            return $this->get_empty_bookmarks_message();
        }

        return $this->render_bookmarked_posts($categorized_posts);
    }

    private function categorize_posts($post_ids)
    {
        $categorized_posts = [];

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $categories = get_the_category($post_id);
            if (empty($categories)) {
                continue;
            }

            $category_name = $categories[0]->name;
            if (!isset($categorized_posts[$category_name])) {
                $categorized_posts[$category_name] = [];
            }

            $categorized_posts[$category_name][] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'chapter' => get_post_meta($post_id, 'chapter_number', true) ?: '1'
            ];
        }

        return $categorized_posts;
    }

    private function render_bookmarked_posts($categorized_posts)
    {
        $output = '<div class="cb-bookmarked-posts">';
        foreach ($categorized_posts as $category => $posts) {
            $output .= '<div class="bookmark-category">';
            foreach ($posts as $post) {
                $output .= sprintf(
                    '<div class="bookmark-item">
                        <a href="%s">
                            <strong class="category-name">%s</strong>
                            <span class="chapter">Chapter %s</span>
                        </a>
                        <button class="bookmark-remove" data-post-id="%d" title="Remove bookmark">×</button>
                    </div>',
                    esc_url(get_permalink($post['id'])),
                    esc_html($category),
                    esc_html($post['title']),
                    (int) $post['id']
                );
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function get_empty_bookmarks_message()
    {
        return '<div class="cb-bookmarked-posts"><p>No Bookmarks. <br>Tap heart to leave chapter bookmarks.
</p></div>';
    }
}

// Initialize the plugin
add_action('plugins_loaded', function () {
    CB_Track_Post::get_instance();
});