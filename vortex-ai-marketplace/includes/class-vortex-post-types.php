<?php
/**
 * Register custom post types for the Artist Journey.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class Vortex_Post_Types {

    /**
     * Register all custom post types.
     *
     * @since    2.0.0
     */
    public function register_post_types() {
        $this->register_plans();
        $this->register_wallet();
        $this->register_horas_quiz();
        $this->register_milestone();
        $this->register_collection();
        $this->register_listing();
    }

    /**
     * Register the Plans custom post type.
     *
     * @since    2.0.0
     */
    private function register_plans() {
        $labels = array(
            'name'                  => _x('Plans', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Plan', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('Subscription Plans', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Plan', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Plan', 'vortex-ai-marketplace'),
            'new_item'              => __('New Plan', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Plan', 'vortex-ai-marketplace'),
            'view_item'             => __('View Plan', 'vortex-ai-marketplace'),
            'all_items'             => __('All Plans', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Plans', 'vortex-ai-marketplace'),
            'parent_item_colon'     => __('Parent Plans:', 'vortex-ai-marketplace'),
            'not_found'             => __('No plans found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No plans found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'plans'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'plans',
            'supports'           => array('title', 'editor', 'custom-fields'),
        );

        register_post_type('vortex_plan', $args);
    }

    /**
     * Register the Wallet custom post type.
     *
     * @since    2.0.0
     */
    private function register_wallet() {
        $labels = array(
            'name'                  => _x('Wallets', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Wallet', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('User Wallets', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Wallet', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Wallet', 'vortex-ai-marketplace'),
            'new_item'              => __('New Wallet', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Wallet', 'vortex-ai-marketplace'),
            'view_item'             => __('View Wallet', 'vortex-ai-marketplace'),
            'all_items'             => __('All Wallets', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Wallets', 'vortex-ai-marketplace'),
            'not_found'             => __('No wallets found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No wallets found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'wallets'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'wallets',
            'supports'           => array('title', 'custom-fields'),
        );

        register_post_type('vortex_wallet', $args);
    }

    /**
     * Register the Horas Quiz custom post type.
     *
     * @since    2.0.0
     */
    private function register_horas_quiz() {
        $labels = array(
            'name'                  => _x('Horas Quizzes', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Horas Quiz', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('Business Quizzes', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Horas Quiz', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Quiz Response', 'vortex-ai-marketplace'),
            'new_item'              => __('New Quiz Response', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Quiz Response', 'vortex-ai-marketplace'),
            'view_item'             => __('View Quiz Response', 'vortex-ai-marketplace'),
            'all_items'             => __('All Quiz Responses', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Quiz Responses', 'vortex-ai-marketplace'),
            'not_found'             => __('No quiz responses found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No quiz responses found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'horas-quiz'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'horas-quiz',
            'supports'           => array('title', 'editor', 'custom-fields'),
        );

        register_post_type('vortex_horas_quiz', $args);
    }

    /**
     * Register the Milestone custom post type.
     *
     * @since    2.0.0
     */
    private function register_milestone() {
        $labels = array(
            'name'                  => _x('Milestones', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Milestone', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('User Milestones', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Milestone', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Milestone', 'vortex-ai-marketplace'),
            'new_item'              => __('New Milestone', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Milestone', 'vortex-ai-marketplace'),
            'view_item'             => __('View Milestone', 'vortex-ai-marketplace'),
            'all_items'             => __('All Milestones', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Milestones', 'vortex-ai-marketplace'),
            'not_found'             => __('No milestones found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No milestones found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'milestones'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'milestones',
            'supports'           => array('title', 'editor', 'custom-fields'),
        );

        register_post_type('vortex_milestone', $args);
    }

    /**
     * Register the Collection custom post type.
     *
     * @since    2.0.0
     */
    private function register_collection() {
        $labels = array(
            'name'                  => _x('Collections', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Collection', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('Art Collections', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Collection', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Collection', 'vortex-ai-marketplace'),
            'new_item'              => __('New Collection', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Collection', 'vortex-ai-marketplace'),
            'view_item'             => __('View Collection', 'vortex-ai-marketplace'),
            'all_items'             => __('All Collections', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Collections', 'vortex-ai-marketplace'),
            'not_found'             => __('No collections found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No collections found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'collections'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'collections',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
        );

        register_post_type('vortex_collection', $args);
    }

    /**
     * Register the Listing custom post type.
     *
     * @since    2.0.0
     */
    private function register_listing() {
        $labels = array(
            'name'                  => _x('Listings', 'Post type general name', 'vortex-ai-marketplace'),
            'singular_name'         => _x('Listing', 'Post type singular name', 'vortex-ai-marketplace'),
            'menu_name'             => _x('Marketplace Listings', 'Admin Menu text', 'vortex-ai-marketplace'),
            'name_admin_bar'        => _x('Listing', 'Add New on Toolbar', 'vortex-ai-marketplace'),
            'add_new'               => __('Add New', 'vortex-ai-marketplace'),
            'add_new_item'          => __('Add New Listing', 'vortex-ai-marketplace'),
            'new_item'              => __('New Listing', 'vortex-ai-marketplace'),
            'edit_item'             => __('Edit Listing', 'vortex-ai-marketplace'),
            'view_item'             => __('View Listing', 'vortex-ai-marketplace'),
            'all_items'             => __('All Listings', 'vortex-ai-marketplace'),
            'search_items'          => __('Search Listings', 'vortex-ai-marketplace'),
            'not_found'             => __('No listings found.', 'vortex-ai-marketplace'),
            'not_found_in_trash'    => __('No listings found in Trash.', 'vortex-ai-marketplace'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'vortex-ai-marketplace',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'listings'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'show_in_rest'       => true,
            'rest_base'          => 'listings',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
        );

        register_post_type('vortex_listing', $args);
    }
} 