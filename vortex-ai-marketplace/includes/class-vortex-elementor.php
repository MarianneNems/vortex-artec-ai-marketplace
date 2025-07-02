<?php
/**
 * Elementor widgets integration for VORTEX AI marketplace.
 */
class Vortex_Elementor {

    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_widget_categories'));
    }

    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'vortex-ai',
            array(
                'title' => 'VORTEX AI',
                'icon' => 'fa fa-plug',
            )
        );
    }

    public function register_widgets() {
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/class-plans-widget.php';
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/class-wallet-widget.php';
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/class-quiz-widget.php';
        require_once plugin_dir_path(__FILE__) . 'elementor-widgets/class-gallery-widget.php';

        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Vortex_Plans_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Vortex_Wallet_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Vortex_Quiz_Widget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Vortex_Gallery_Widget());
    }
} 