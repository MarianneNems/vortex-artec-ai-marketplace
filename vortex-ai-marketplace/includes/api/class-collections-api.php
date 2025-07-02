<?php
/**
 * Collections API handler for managing user artwork collections.
 */
class Vortex_Collections_API {

    private $namespace = 'vortex/v1';

    public function register_routes() {
        register_rest_route($this->namespace, '/collections', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_collections'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/collections', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_collection'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/collections/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_collection'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_collections($request) {
        $user_id = $request->get_param('user_id');
        $per_page = $request->get_param('per_page') ?: 12;
        
        $args = array(
            'post_type' => 'vortex_collection',
            'posts_per_page' => $per_page,
        );
        
        if ($user_id) {
            $args['author'] = $user_id;
        }
        
        $collections = get_posts($args);
        $response_data = array();
        
        foreach ($collections as $collection) {
            $response_data[] = array(
                'id' => $collection->ID,
                'title' => $collection->post_title,
                'description' => $collection->post_content,
                'artwork_count' => $this->get_artwork_count($collection->ID),
                'thumbnail' => get_the_post_thumbnail_url($collection->ID),
                'created_at' => $collection->post_date,
                'author' => get_user_by('ID', $collection->post_author)->display_name,
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'collections' => $response_data,
            'total' => count($response_data),
        ), 200);
    }

    public function create_collection($request) {
        $title = $request->get_param('title');
        $description = $request->get_param('description');
        $artwork_ids = $request->get_param('artwork_ids') ?: array();
        
        if (empty($title)) {
            return new WP_Error('missing_title', 'Collection title is required', array('status' => 400));
        }
        
        $collection_id = wp_insert_post(array(
            'post_type' => 'vortex_collection',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));
        
        if ($collection_id && !empty($artwork_ids)) {
            update_post_meta($collection_id, 'vortex_collection_artworks', $artwork_ids);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'collection_id' => $collection_id,
            'message' => 'Collection created successfully',
        ), 201);
    }

    public function get_collection($request) {
        $collection_id = $request->get_param('id');
        $collection = get_post($collection_id);
        
        if (!$collection || $collection->post_type !== 'vortex_collection') {
            return new WP_Error('collection_not_found', 'Collection not found', array('status' => 404));
        }
        
        $artwork_ids = get_post_meta($collection_id, 'vortex_collection_artworks', true) ?: array();
        $artworks = array();
        
        foreach ($artwork_ids as $artwork_id) {
            $artwork = get_post($artwork_id);
            if ($artwork) {
                $artworks[] = array(
                    'id' => $artwork->ID,
                    'title' => $artwork->post_title,
                    'image_url' => get_the_post_thumbnail_url($artwork->ID),
                );
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'collection' => array(
                'id' => $collection->ID,
                'title' => $collection->post_title,
                'description' => $collection->post_content,
                'artworks' => $artworks,
                'artwork_count' => count($artworks),
                'created_at' => $collection->post_date,
                'author' => get_user_by('ID', $collection->post_author)->display_name,
            ),
        ), 200);
    }

    private function get_artwork_count($collection_id) {
        $artwork_ids = get_post_meta($collection_id, 'vortex_collection_artworks', true) ?: array();
        return count($artwork_ids);
    }

    public function check_user_permission($request) {
        return is_user_logged_in();
    }
} 