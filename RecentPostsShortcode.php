<?php

/**
 * Plugin Name: Recent Posts Shortcode
 * Description: Отображение последних записей по ТЗ
 * Version: 1.0
 * Author: TatrokovZM
 */
if (!defined('ABSPATH')) {
    exit;
}

require WP_PLUGIN_DIR . '/wp-monolog-master/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class RecentPostsShortcode
{
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger = new Logger('MY-log');
        $this->logger->pushHandler(new StreamHandler(plugin_dir_path(__FILE__) . 'plugin.log', Logger::INFO));

        add_shortcode('recent_posts', [$this, 'render_recent_posts']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function render_recent_posts($atts)
    {
        $atts = shortcode_atts([
            'count' => get_option('recent_posts_count', 10),
        ], $atts, 'recent_posts');

        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => intval($atts['count']),
        ];

        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            $this->logger->error('No posts found');
            return 'No posts found.';
        }
        $this->logger->warning("Number of Posts: ", ['count'  => $atts['count']]);
        $output = '<ul>';

        while ($query->have_posts()) {
            $query->the_post();
            $output .= sprintf('<li><a href="%1$s">%2$s</a></li>', get_permalink(), get_the_title());
        }

        wp_reset_postdata();

        $output .= '</ul>';

        return $output;
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Recent Posts Settings',
            'Recent Posts',
            'manage_options',
            'recent-posts-shortcode',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page()
    {
?>
        <div class="wrap">
            <h1>Recent Posts Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('recent_posts_settings');
                do_settings_sections('recent-posts-shortcode');
                submit_button();
                ?>
            </form>
            Логирование <a href="/wp-content/plugins/RecentPostsShortcode/plugin.log" target="_blank">тут</a>.
        </div>
<?php
    }

    public function register_settings()
    {
        register_setting('recent_posts_settings', 'recent_posts_count');

        add_settings_section(
            'recent_posts_settings_section',
            'Settings',
            null,
            'recent-posts-shortcode'
        );

        add_settings_field(
            'recent_posts_count',
            'Number of Posts',
            [$this, 'render_count_field'],
            'recent-posts-shortcode',
            'recent_posts_settings_section'
        );
    }

    public function render_count_field()
    {
        $value = get_option('recent_posts_count', 10);
        echo '<input type="number" name="recent_posts_count" value="' . esc_attr($value) . '" min="1" max="100">';
    }




    function wpcf_plugin_action_links($actions, $plugin_file)
    {

        if (false === strpos($plugin_file, basename(__FILE__))) {
            return $actions;
        }

        $settings_link = '<a href="options-general.php?page=' . basename(dirname(__FILE__)) . '/options.php' . '">Settings</a>';
        array_unshift($actions, $settings_link);

        return $actions;
    }
}
$recentPostsShortcode = new RecentPostsShortcode($logger);
