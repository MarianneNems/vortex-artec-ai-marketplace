VORTEX AI Marketplace is a WordPress plugin that enables the creation of an AI-powered digital asset marketplace with TOLA token integration for cryptocurrency payments.

TOLA Token Integration

The TOLA token integration allows users to connect their Web3 wallets, check token balances, and make transactions directly within your WordPress site.

Features

Wallet Connection: Connect MetaMask or other Web3 wallets

Balance Checking: Real-time TOLA token balance display

Send Tokens: Transfer TOLA tokens to other users

Transaction History: View your transaction history

Blockchain Verification: Automatic verification of transaction status

Fallback Mechanism: Cached balances when blockchain is unavailable

Installation

Download the ZIP file of the plugin

Go to WordPress Admin > Plugins > Add New > Upload Plugin

Select the ZIP file and click "Install Now"

Activate the plugin

Configuration

TOLA Token Settings

Navigate to VORTEX > Settings > Blockchain

Enter your TOLA token contract address

Enter your Web3 provider URL (e.g., Infura endpoint)

Set token decimals (default: 18)

Database Initialization

The plugin will automatically create the necessary database tables when activated. If you need to manually initialize the database:

.php

$tola = new Vortex_TOLA();

$tola->create_database_tables();

Usage

Shortcodes

Display a user's TOLA balance:  
[vortex_tola_balance]

Display a specific address's balance:  
[vortex_tola_balance address="0x123..."]

Display the full wallet interface:  
[vortex_tola_wallet]

Hide transaction history:
[vortex_tola_wallet show_transactions="false"]

Hide send functionality:
[vortex_tola_wallet show_send="false"]

Developer Notes

The plugin requires Web3.php library (included)

Tested with WordPress 5.8+

Compatible with various Web3 wallet providers

Requirements

WordPress 5.6 or higher

PHP 7.4 or higher

Web3-compatible browser or extension

Credits

Created, developed by Marianne Nems (aka Mariana Villard, all rights reserved, 2025) for the VORTEX AI Marketplace platform.

License

This project is licensed under the GPL v2 or later.

Support

For support, please open an issue on this GitHub repository or contact the maintainer.
