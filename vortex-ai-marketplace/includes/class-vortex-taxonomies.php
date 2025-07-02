<?php
/**
 * Register all taxonomies for the plugin.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Taxonomies {

    /**
     * Register taxonomies.
     *
     * @since    2.0.0
     */
    public function register_taxonomies() {
        $this->register_artwork_category();
        $this->register_artist_category();
        $this->register_collection_category();
    }

    /**
     * Register artwork category taxonomy.
     */
    private function register_artwork_category() {
        $labels = array(
            'name'              => _x('Artwork Categories', 'taxonomy general name', 'vortex-ai-marketplace'),
            'singular_name'     => _x('Artwork Category', 'taxonomy singular name', 'vortex-ai-marketplace'),
            'search_items'      => __('Search Artwork Categories', 'vortex-ai-marketplace'),
            'all_items'         => __('All Artwork Categories', 'vortex-ai-marketplace'),
            'edit_item'         => __('Edit Artwork Category', 'vortex-ai-marketplace'),
            'update_item'       => __('Update Artwork Category', 'vortex-ai-marketplace'),
            'add_new_item'      => __('Add New Artwork Category', 'vortex-ai-marketplace'),
            'new_item_name'     => __('New Artwork Category Name', 'vortex-ai-marketplace'),
            'menu_name'         => __('Artwork Categories', 'vortex-ai-marketplace'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'artwork-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('artwork_category', array('artwork'), $args);
    }

    /**
     * Register artist category taxonomy.
     */
    private function register_artist_category() {
        $labels = array(
            'name'              => _x('Artist Categories', 'taxonomy general name', 'vortex-ai-marketplace'),
            'singular_name'     => _x('Artist Category', 'taxonomy singular name', 'vortex-ai-marketplace'),
            'search_items'      => __('Search Artist Categories', 'vortex-ai-marketplace'),
            'all_items'         => __('All Artist Categories', 'vortex-ai-marketplace'),
            'edit_item'         => __('Edit Artist Category', 'vortex-ai-marketplace'),
            'update_item'       => __('Update Artist Category', 'vortex-ai-marketplace'),
            'add_new_item'      => __('Add New Artist Category', 'vortex-ai-marketplace'),
            'new_item_name'     => __('New Artist Category Name', 'vortex-ai-marketplace'),
            'menu_name'         => __('Artist Categories', 'vortex-ai-marketplace'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'artist-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('artist_category', array('artist'), $args);
    }

    /**
     * Register collection category taxonomy.
     */
    private function register_collection_category() {
        $labels = array(
            'name'              => _x('Collection Categories', 'taxonomy general name', 'vortex-ai-marketplace'),
            'singular_name'     => _x('Collection Category', 'taxonomy singular name', 'vortex-ai-marketplace'),
            'search_items'      => __('Search Collection Categories', 'vortex-ai-marketplace'),
            'all_items'         => __('All Collection Categories', 'vortex-ai-marketplace'),
            'edit_item'         => __('Edit Collection Category', 'vortex-ai-marketplace'),
            'update_item'       => __('Update Collection Category', 'vortex-ai-marketplace'),
            'add_new_item'      => __('Add New Collection Category', 'vortex-ai-marketplace'),
            'new_item_name'     => __('New Collection Category Name', 'vortex-ai-marketplace'),
            'menu_name'         => __('Collection Categories', 'vortex-ai-marketplace'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'collection-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('collection_category', array('collection'), $args);
    }
} 