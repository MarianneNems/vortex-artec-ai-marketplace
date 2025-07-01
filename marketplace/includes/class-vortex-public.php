	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Vortex_AI_Marketplace_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		// Register shortcodes
		add_shortcode( 'vortex_artist_registration', array( $plugin_public, 'artist_registration_shortcode' ) );
		add_shortcode( 'vortex_artist_dashboard', array( $plugin_public, 'artist_dashboard_shortcode' ) );
		add_shortcode( 'vortex_artwork_submit', array( $plugin_public, 'artwork_submit_shortcode' ) );
		add_shortcode( 'vortex_artwork_gallery', array( $plugin_public, 'artwork_gallery_shortcode' ) );
		add_shortcode( 'vortex_ai_agents', array( $plugin_public, 'ai_agents_shortcode' ) );
		add_shortcode( 'vortex_collector_purchases', array( $plugin_public, 'collector_purchases_shortcode' ) );
		
		// ... existing code ...
	} 