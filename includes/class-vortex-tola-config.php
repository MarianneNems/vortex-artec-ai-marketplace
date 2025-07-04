<?php
/**
 * TOLA Token Configuration Class
 *
 * Centralizes all TOLA token configuration and settings
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * TOLA Token Configuration Class.
 *
 * This class handles all TOLA token configuration, blockchain settings,
 * and tokenomics parameters for the VortexArtec AI Marketplace.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems
 */
class Vortex_TOLA_Config {

    /**
     * The single instance of the class.
     *
     * @var Vortex_TOLA_Config
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main Vortex_TOLA_Config Instance.
     *
     * Ensures only one instance of Vortex_TOLA_Config is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return Vortex_TOLA_Config - Main instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize configuration
    }

    /**
     * Get TOLA token information
     *
     * @since    1.0.0
     * @return   array    Token information
     */
    public function get_token_info() {
        return array(
            'name' => 'TOLA (Token of Love and Appreciation)',
            'symbol' => 'TOLA',
            'contract_address' => 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky',
            'blockchain' => 'solana',
            'token_standard' => 'SPL',
            'decimals' => 9,
            'total_supply' => 50000000, // 50M TOLA
            'original_supply' => 1000000000, // 1B TOLA
            'burned_amount' => 950000000, // 950M TOLA burned
            'investor_price' => 0.60, // $0.60 per TOLA
            'rpc_endpoint' => 'https://api.mainnet-beta.solana.com'
        );
    }

    /**
     * Get fee structure
     *
     * @since    1.0.0
     * @return   array    Fee structure
     */
    public function get_fee_structure() {
        return array(
            'primary_sale' => array(
                'vortex_creator' => 5, // 5%
                'platform_treasury' => 15, // 15%
                'artist' => 80 // 80%
            ),
            'secondary_sale' => array(
                'vortex_creator' => 5, // 5%
                'original_artist' => 15, // 15%
                'platform_treasury' => 15, // 15%
                'seller' => 65 // 65%
            )
        );
    }

    /**
     * Get reward amounts
     *
     * @since    1.0.0
     * @return   array    Reward amounts
     */
    public function get_rewards() {
        return array(
            'artist_rewards' => array(
                'profile_setup' => 500,
                'upload_artwork' => 250,
                'publish_blog_post' => 200,
                'trade_artwork' => 500,
                'make_sale' => 700,
                'weekly_top_10' => 1000,
                'refer_artist' => 1000,
                'refer_collector' => 1000
            ),
            'collector_perks' => array(
                'cashback_rate' => array('min' => 1, 'max' => 3), // 1-3%
                'vip_staking_minimum' => 5000,
                'quarterly_airdrops' => true
            ),
            'daily_users' => array(
                'micro_staking_minimum' => 50,
                'micro_staking_daily_reward' => 5,
                'referral_bonus' => 50,
                'event_entry_burn' => 50
            )
        );
    }

    /**
     * Get subscription tiers
     *
     * @since    1.0.0
     * @return   array    Subscription tier information
     */
    public function get_subscriptions() {
        return array(
            'standard' => array(
                'price_usd' => 19,
                'price_tola' => 19, // 1 TOLA = 1 USDC
                'benefits' => 'Basic analytics, up to 100 uploads/month'
            ),
            'essential' => array(
                'price_usd' => 49,
                'price_tola' => 49,
                'benefits' => 'Advanced analytics, up to 500 uploads, priority support'
            ),
            'premium' => array(
                'price_usd' => 99,
                'price_tola' => 99,
                'benefits' => 'Full analytics, unlimited uploads, dedicated manager'
            )
        );
    }

    /**
     * Get contract address
     *
     * @since    1.0.0
     * @return   string    The TOLA contract address
     */
    public function get_contract_address() {
        return 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky';
    }

    /**
     * Get RPC endpoint
     *
     * @since    1.0.0
     * @param    string    $network    The network (mainnet, devnet, testnet)
     * @return   string    RPC endpoint URL
     */
    public function get_rpc_endpoint($network = 'mainnet') {
        switch ($network) {
            case 'devnet':
                return 'https://api.devnet.solana.com';
            case 'testnet':
                return 'https://api.testnet.solana.com';
            default:
                return 'https://api.mainnet-beta.solana.com';
        }
    }

    /**
     * Get primary sale fee percentage for specific recipient
     *
     * @since    1.0.0
     * @param    string    $recipient    The recipient (vortex_creator, platform_treasury, artist)
     * @return   int    Fee percentage
     */
    public function get_primary_sale_fee($recipient) {
        $fees = $this->get_fee_structure();
        return isset($fees['primary_sale'][$recipient]) 
            ? $fees['primary_sale'][$recipient] 
            : 0;
    }

    /**
     * Get secondary sale fee percentage for specific recipient
     *
     * @since    1.0.0
     * @param    string    $recipient    The recipient
     * @return   int    Fee percentage
     */
    public function get_secondary_sale_fee($recipient) {
        $fees = $this->get_fee_structure();
        return isset($fees['secondary_sale'][$recipient]) 
            ? $fees['secondary_sale'][$recipient] 
            : 0;
    }

    /**
     * Get supported wallets
     *
     * @since    1.0.0
     * @return   array    Supported wallet information
     */
    public function get_supported_wallets() {
        return array(
            'phantom' => array(
                'name' => 'Phantom',
                'url' => 'https://phantom.app/',
                'primary' => true
            ),
            'solflare' => array(
                'name' => 'Solflare',
                'url' => 'https://solflare.com/',
                'primary' => false
            ),
            'sollet' => array(
                'name' => 'Sollet',
                'url' => 'https://www.sollet.io/',
                'primary' => false
            ),
            'ledger' => array(
                'name' => 'Ledger',
                'url' => 'https://www.ledger.com/',
                'primary' => false
            )
        );
    }

    /**
     * Get burn forecast
     *
     * @since    1.0.0
     * @return   array    Token burn forecast
     */
    public function get_burn_forecast() {
        return array(
            array('month' => 1, 'start' => 50000000, 'burned' => 50000, 'end' => 49950000),
            array('month' => 6, 'start' => 49700000, 'burned' => 250000, 'end' => 49450000),
            array('month' => 12, 'start' => 49200000, 'burned' => 500000, 'end' => 48700000)
        );
    }
}

// Initialize the configuration
function vortex_tola_config() {
    return Vortex_TOLA_Config::instance();
}

// Global function to get TOLA config
if (!function_exists('get_tola_config')) {
    function get_tola_config() {
        return vortex_tola_config();
    }
}
