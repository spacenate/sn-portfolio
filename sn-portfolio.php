<?php
/*
Plugin Name: SN Portfolio
Description: Custom Post Type for portfolio pieces
Version: 1.0
Author: Nathan Sollenberger <nathanthehuman@spacenate.com>
Author URI: https://www.spacenate.com
License: GPLv2
*/

if (!defined('ABSPATH')) {
    exit;
}

class SNPortfolioPostTypes {

    static $nonce_name = 'sn_portfolio_nce_';

    function __construct()
    {
        $this->registerPostType();
        $this->registerTaxonomies();

        if (is_admin()) {
            $this->adminEnqueueScripts();
            $this->adminListenForSavePost();
            $this->adminAddSettingsPage();
            $this->adminAddCustomColumns();
        }
        else {
            $this->publicEnqueueScripts();
            $this->publicOverrideTemplates();
            $this->publicOverrideBloginfo();
        }
    }

    /**
     * Register custom post type.
     */
    function registerPostType() {
        // Register post type
        add_action('init', function() {
            register_post_type('sn_portfolio', array(
                    'labels'      => array(
                        'name'          => __('Portfolio'),
                        'singular_name' => __('Portfolio piece')
                    ),
                    'public'      => true,
                    'has_archive' => true,
                    'rewrite'     => array('slug' => 'portfolio', 'with_front' => false),
                    'supports'    => array('title', 'editor', 'author', 'revisions'),
                    'register_meta_box_cb' => array($this, 'registerMetaBox')
                )
            );
        });
    }

    /**
     * Register custom post taxonomies.
     *
     * @todo figure out if we want to keep Media Type taxonomy
     */
    function registerTaxonomies() {
        add_action('init', function() {
            // Featured
            $labels = array(
                'name'          => _x('Featured', 'taxonomy general name'),
                'singular_name' => _x('Featured Piece', 'taxonomy singular name')
            );

            $params = array(
                'hierarchical'  => false,
                'labels'        => $labels,
                'public'        => false,
                'show_ui'       => false,
                'show_in_menu'  => false,
                'show_admin_column' => false
            );

            register_taxonomy('sn_portfolio_featured', 'sn_portfolio', $params);

            // Media Type
            $labels = array(
                'name'          => _x('Media Types', 'taxonomy general name'),
                'singular_name' => _x('Media Type', 'taxonomy singular name'),
                'search_items'  => __('Search Media'),
                'all_items'     => __('All Media'),
                'edit_item'     => __('Edit Media Type'),
                'update_item'   => __('Update Media Type'),
                'add_new_item'  => __('Add New Media Type'),
                'menu'          => __('Media Type')
            );

            $params = array(
                'hierarchical'  => false,
                'labels'        => $labels,
                'public'        => true,
                'show_ui'       => false,
                'show_in_menu'  => false,
                'show_admin_column' => true,
                'query_var'     => true,
                'rewrite'       => array('slug' => 'media-type', 'with_front' => false),
            );

            register_taxonomy('sn_portfolio_media_type', 'sn_portfolio', $params);
        });
    }

    /**
     * Register custom post meta box.
     *
     * Registers callback to generate meta box markup.
     */
    function registerMetaBox() {
        add_meta_box(
            'sn_portfolio_meta',
            __('Portfolio Piece Settings'),
            array($this, 'generateMetaBox'),
            'sn_portfolio',
            'side',
            'default'
        );
    }

    /**
     * Output custom post meta box markup.
     *
     * @param WP_Post $post
     * @param array $box
     */
    function generateMetaBox( WP_Post $post, array $box ) {
        $vm = array(
            'featured'     => wp_get_object_terms($post->ID, 'sn_portfolio_featured')[0]->slug,
            'media_type'   => wp_get_object_terms($post->ID, 'sn_portfolio_media_type')[0]->slug,
            'media_source' => get_post_meta($post->ID, 'sn_portfolio_media_source', true),
            'title'        => get_post_meta($post->ID, 'sn_portfolio_title', true),
            'description'  => get_post_meta($post->ID, 'sn_portfolio_description', true),
            'nonce_action' => basename(__FILE__),
            'nonce_name'   => self::$nonce_name,
            'media_types'  => array(
                'written'    => 'Written',
                'video'      => 'Video',
                'app-copy'   => 'App Copy',
                'print-copy' => 'Print Copy'
            )
        );

        include(plugin_dir_path(__FILE__) . 'admin-metabox.php');
    }

    /**
     * Enqueue custom post javascript and stylesheet files for use in WP Admin.
     */
    function adminEnqueueScripts() {
        add_action('admin_enqueue_scripts', function( $hook ) {
            if ($hook === 'sn_portfolio_page_sn_portfolio_settings') {
                $script_path = plugins_url('sn-portfolio-admin.js', __FILE__);
                wp_enqueue_script('sn-portfolio-admin', $script_path);

                $style_path = plugins_url('sn-portfolio-admin.css', __FILE__);
                wp_enqueue_style('sn-portfolio-admin', $style_path);
            }
        });
    }

    /**
     * Listen for custom post being saved in admin.
     *
     * Updates taxonomies and post meta using form inputs in custom meta box.
     */
    function adminListenForSavePost() {
        add_action('save_post', function( $post_id, $post ) {
            if (!isset($post->post_type) || $post->post_type !== 'sn_portfolio') {
                return $post_id;
            }

            $post_type = get_post_type_object($post->post_type);
            if (!current_user_can($post_type->cap->edit_post, $post_id)) {
                return $post_id;
            }

            if (!isset($_POST[self::$nonce_name]) || !wp_verify_nonce($_POST[self::$nonce_name], basename(__FILE__))) {
                return $post_id;
            }

            // Update "Featured" taxonomy
            wp_set_object_terms($post_id, $_POST['sn_portfolio_featured'], 'sn_portfolio_featured');

            // When a post is being set for the first time, initialize the "Featured Order" post meta
            if (empty(get_post_meta($post_id, 'sn_portfolio_featured_order', true))) {
                $last_position = (int) get_option('sn_portfolio_total_count', 0) + 1;
                update_post_meta($post_id, 'sn_portfolio_featured_order', $last_position);
                update_option('sn_portfolio_total_count', $last_position);
            }

            // Update "Media Type" taxonomy
            wp_set_object_terms($post_id, $_POST['sn_portfolio_media_type'], 'sn_portfolio_media_type');

            // Update "Source" post meta
            update_post_meta($post_id, 'sn_portfolio_media_source', $_POST['sn_portfolio_media_source']);

            // Update "Portfolio Index Title" post meta
            update_post_meta($post_id, 'sn_portfolio_title', $_POST['sn_portfolio_title']);

            // Update "Portfolio Index Description" post meta
            update_post_meta($post_id, 'sn_portfolio_description', $_POST['sn_portfolio_description']);

            return $post_id;
        }, 10, 2);
    }

    /**
     * Add custom post settings page in admin.
     *
     * Registers settings group and setting fields, adds submenu, and registers callback
     * to generate settings page markup.
     */
    function adminAddSettingsPage() {
        // Add settings page to menu
        add_action('admin_menu', function() {
            add_submenu_page(
                'edit.php?post_type=sn_portfolio',
                'Portfolio Settings',
                'Settings',
                'manage_options',
                'sn_portfolio_settings',
                array($this, 'generateSettingsPage')
            );
        });

        // Register settings
        add_action('admin_init', function() {
            register_setting('sn_portfolio', 'sn_portfolio_settings', array($this, 'saveSettingsCallback'));

            add_settings_section(
                'bloginfo_override',
                __('Index Page'),
                array($this, 'generateSettingsSectionBloginfoOverride'),
                'sn_portfolio'
            );

            add_settings_field(
                'bloginfo_name',
                __('Blog title'),
                array($this, 'generateSettingsFieldBloginfoName'),
                'sn_portfolio',
                'bloginfo_override'
            );

            add_settings_field(
                'bloginfo_description',
                __('Blog description'),
                array($this, 'generateSettingsFieldBloginfoDescription'),
                'sn_portfolio',
                'bloginfo_override'
            );
        });
    }

    /**
     * Output settings page markup.
     */
    function generateSettingsPage() {
        $featured_query_args = array(
            'post_type'      => 'sn_portfolio',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => 'sn_portfolio_featured_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC'
        );

        $vm = array(
            'settings'       => 'sn_portfolio',
            'featured_query' => new WP_Query($featured_query_args)
        );

        include(plugin_dir_path(__FILE__) . 'admin-settings.php');
    }

    function generateSettingsSectionBloginfoOverride() {
        print(__('Override your normal blog title and description on Portfolio pages (optional, leave blank to use default).'));
    }

    function generateSettingsFieldBloginfoName() {
        $options = get_option('sn_portfolio_settings');
        printf('<input type="text" name="sn_portfolio_settings[bloginfo_name]" value="%s">', $options['bloginfo_name']);
    }

    function generateSettingsFieldBloginfoDescription() {
        $options = get_option('sn_portfolio_settings');
        printf('<input type="text" name="sn_portfolio_settings[bloginfo_description]" value="%s">', $options['bloginfo_description']);
    }

    /**
     * Save 'Featured' settings for each post individually as taxonomy/post meta.
     *
     * Technically this callback is intended for sanitizing user input from normal settings fields;
     * it is invoked by WP when the custom settings page in admin is being saved.
     *
     * @param array $input
     * @return array
     */
    function saveSettingsCallback( $input ) {
        $featured       = $_POST['sn_portfolio_featured'];
        $featured_order = $_POST['sn_portfolio_featured_order'];

        foreach ($featured_order as $index => $featured_post_id) {
            // User-facing value, convert to 1-index
            ++$index;
            // Update "Featured Order" post meta
            update_post_meta((int) $featured_post_id, 'sn_portfolio_featured_order', (string) $index);
            // Update "Featured" taxonomy
            wp_set_object_terms((int) $featured_post_id, $featured[$featured_post_id], 'sn_portfolio_featured');
        }

        return $input;
    }

    /**
     * Add sortable 'Featured' column to admin.
     */
    function adminAddCustomColumns() {
        add_filter('manage_sn_portfolio_posts_columns', function( $columns ) {
            // Remove "Author" column
            unset($columns['author']);
            // Add "Featured" column
            $columns = $this->insertArrayAtPosition($columns, array('featured_order' => _x('Featured', 'taxonomy general name')), 2);
            // Add "Status" column
            // $columns = $this->insertArrayAtPosition($columns, array('status' => __('Status')), 2);
            return $columns;
        });

        add_action('manage_posts_custom_column', function( $column_name, $post_id ) {
            if ('featured_order' === $column_name) {
                if (wp_get_object_terms($post_id, 'sn_portfolio_featured')[0]->slug === '1') {
                    print(get_post_meta($post_id, 'sn_portfolio_featured_order', true));
                }
                else {
                    printf('<em>%s</em>', __('Not featured'));
                }
            }
            // else if ('status' === $column_name) {
            //     $status_string_map = array(
            //         'publish' => 'Published',
            //         'pending' => 'Pending',
            //         'draft' => 'Draft',
            //         'auto-draft' => 'Draft',
            //         'future' => 'Pending',
            //         'private' => 'Private',
            //         'inherit' => 'Revision',
            //         'trash' => 'Deleted'
            //     );
            //     $status = get_post_status($post_id);
            //     if (isset($status_string_map[$status])) {
            //         print(__($status_string_map[$status]));
            //     }
            //     else {
            //         printf('<em>%s</em>', __('Unknown'));
            //     }
            // }
        }, 10, 2);


        add_filter('manage_edit-sn_portfolio_sortable_columns', function( $cols ) {
            $cols['featured_order'] = 'featured_order';
            return $cols;
        });

        add_filter('request', function( $vars ) {
            if (isset($vars['orderby']) && 'featured_order' === $vars['orderby']) {
                $vars = array_merge($vars, array(
                    'meta_key' => 'sn_portfolio_featured_order',
                    'orderby' => 'meta_value_num'
                ));
            }
            return $vars;
        });
    }

    /**
     * Enqueue javascript and stylesheet files for use in public (Archive and Single post) pages
     */
    function publicEnqueueScripts() {
        add_action('wp_enqueue_scripts', function() {
            $style_path = plugins_url('sn-portfolio.css', __FILE__);
            wp_enqueue_style('sn-portfolio', $style_path);
        });
    }

    /**
     * Listen for custom post pages being loaded, and override default (Archive and Single) templates
     * with custom templates.
     *
     * If a user has provided their own custom template (archive-portfolio.php or single-portfolio.php)
     * in the themes directory, it will be used instead.
     */
    function publicOverrideTemplates() {
        add_filter('single_template', function( $current_template ) {
            if ('sn_portfolio' === get_post_type()) {
                $template_name = 'single-sn-portfolio.php';
                $template_path = plugin_dir_path(__FILE__) . $template_name;
                $theme_already_overridden = ($current_template === get_stylesheet_directory() . '/' . $template_name);

                if (!$theme_already_overridden && file_exists($template_path)) {
                    return $template_path;
                }
            }
            return $current_template;
        });

        add_filter('archive_template', function( $current_template ) {
            if (is_post_type_archive('sn_portfolio')) {
                $template_name = 'archive-sn-portfolio.php';
                $template_path = plugin_dir_path(__FILE__) . $template_name;
                $theme_already_overridden = ($current_template === get_stylesheet_directory() . '/' . $template_name);

                if (!$theme_already_overridden && file_exists($template_path)) {
                    return $template_path;
                }
            }
            return $current_template;
        });
    }

    /**
     * Override the default blog title and description when loading custom post pages.
     */
    function publicOverrideBloginfo() {
        add_filter('bloginfo', function( $string, $show ) {
            if ('sn_portfolio' === get_post_type()) {
                $options = get_option('sn_portfolio_settings');

                if ('name' === $show && !empty($options['bloginfo_name'])) {
                    $string = $options['bloginfo_name'];
                }
                else if ('description' === $show && !empty($options['bloginfo_description'])) {
                    $string = $options['bloginfo_description'];
                }
            }

            return $string;
        }, 10, 2);
    }

    /**
     * Splice an item in to an array at the specified position. Handy for working with
     * ordered associative arrays.
     *
     * http://stackoverflow.com/a/3354804
     *
     * @param array $array
     * @param array $insert
     * @param int $position
     * @return array
     */
    function insertArrayAtPosition( $array, $insert, $position ) {
        return array_slice($array, 0, $position, TRUE) + $insert + array_slice($array, $position, NULL, TRUE);
    }
}

new SNPortfolioPostTypes();
