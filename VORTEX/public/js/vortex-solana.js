/**
 * Vortex Solana Integration JavaScript
 *
 * Handles Solana wallet integration, including connection, transactions and balance checks.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    // Store connection state
    let solanaWallet = {
        connected: false,
        publicKey: null,
        connection: null,
        adapter: null,
        balance: 0
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initSolana();
        setupEventListeners();
    });

    /**
     * Initialize Solana connection
     */
    async function initSolana() {
        try {
            // Initialize connection to Solana network
            solanaWallet.connection = new solanaWeb3.Connection(vortexSolana.rpcUrl, 'confirmed');
            
            // Check if Phantom or other Solana wallets are available
            const walletAvailable = 
                window.phantom?.solana?.isPhantom || 
                window.solflare?.isSolflare || 
                window.solana?.isSOlana;

            // Show message if no wallet found
            if (!walletAvailable) {
                console.log('No Solana wallet found. Please install Phantom, Solflare, or another Solana wallet.');
                $('.vortex-connect-wallet-button').prop('disabled', true)
                    .text('Wallet Not Found')
                    .attr('title', 'Please install Phantom, Solflare, or another Solana wallet extension.');
            }

            // Check if user is already connected
            const storedAddress = localStorage.getItem('vortex_wallet_address');
            if (storedAddress && window.solana) {
                connectWallet(true);
            }
        } catch (error) {
            console.error('Error initializing Solana connection:', error);
        }
    }

    /**
     * Set up event listeners for wallet interaction
     */
    function setupEventListeners() {
        // Connect wallet button
        $(document).on('click', '.vortex-connect-wallet-button', function(e) {
            e.preventDefault();
            connectWallet();
        });

        // Disconnect wallet button
        $(document).on('click', '.vortex-disconnect-wallet-button', function(e) {
            e.preventDefault();
            disconnectWallet();
        });

        // Copy address button
        $(document).on('click', '.vortex-copy-address-button', function(e) {
            e.preventDefault();
            const address = $(this).data('address');
            copyToClipboard(address);
            
            // Show feedback
            const originalText = $(this).text();
            $(this).text('Copied!');
            setTimeout(() => {
                $(this).text(originalText);
            }, 2000);
        });

        // Send SOL form
        $(document).on('submit', '.vortex-solana-send-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $result = $form.find('.vortex-solana-send-result');
            
            const recipientAddress = $form.find('#recipient_address').val();
            const amount = parseFloat($form.find('#amount').val());
            
            if (!recipientAddress || isNaN(amount) || amount <= 0) {
                $result.html('<div class="error">Please enter a valid recipient and amount.</div>');
                return;
            }
            
            // Disable form while processing
            $form.find('button').prop('disabled', true).text('Processing...');
            $result.html('<div class="processing">Processing transaction...</div>');
            
            sendSolana(recipientAddress, amount)
                .then(signature => {
                    $result.html(`<div class="success">Transaction sent successfully!</div>`);
                    
                    // Clear form
                    $form.find('#recipient_address').val('');
                    $form.find('#amount').val('');
                    
                    // Refresh balance after a short delay
                    setTimeout(() => {
                        getBalance();
                    }, 5000);
                })
                .catch(error => {
                    $result.html(`<div class="error">Error: ${error.message}</div>`);
                })
                .finally(() => {
                    $form.find('button').prop('disabled', false).text('Send');
                });
        });
    }

    /**
     * Connect to Solana wallet
     * 
     * @param {boolean} silent - If true, don't show prompts/alerts
     */
    async function connectWallet(silent = false) {
        try {
            let provider;
            
            // Check for available wallet providers
            if (window.phantom?.solana) {
                provider = window.phantom.solana;
            } else if (window.solflare) {
                provider = window.solflare;
            } else if (window.solana) {
                provider = window.solana;
            } else {
                if (!silent) {
                    alert('No Solana wallet found. Please install Phantom, Solflare, or another Solana wallet extension.');
                }
                return;
            }
            
            // Connect to wallet
            const resp = await provider.connect();
            solanaWallet.publicKey = resp.publicKey.toString();
            solanaWallet.connected = true;
            solanaWallet.adapter = provider;
            
            // Store address in localStorage for reconnection
            localStorage.setItem('vortex_wallet_address', solanaWallet.publicKey);
            
            // Update UI
            updateWalletUI();
            
            // Get balance
            getBalance();
            
        } catch (error) {
            console.error('Error connecting to wallet:', error);
            if (!silent) {
                alert('Error connecting to wallet: ' + error.message);
            }
        }
    }

    /**
     * Disconnect from wallet
     */
    async function disconnectWallet() {
        try {
            // Try to disconnect if adapter is available
            if (solanaWallet.adapter && solanaWallet.adapter.disconnect) {
                await solanaWallet.adapter.disconnect();
            }
            
            // Reset wallet state
            solanaWallet.connected = false;
            solanaWallet.publicKey = null;
            localStorage.removeItem('vortex_wallet_address');
            
            // Send disconnect request to server
            $.ajax({
                url: vortexSolana.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_disconnect_wallet',
                    nonce: vortexSolana.nonce
                },
                success: function(response) {
                    // Reload page to refresh UI
                    window.location.reload();
                },
                error: function(error) {
                    console.error('Error disconnecting wallet:', error);
                }
            });
        } catch (error) {
            console.error('Error disconnecting wallet:', error);
            alert('Error disconnecting wallet: ' + error.message);
        }
    }

    /**
     * Get SOL balance for connected wallet
     */
    async function getBalance() {
        if (!solanaWallet.connected || !solanaWallet.publicKey) {
            return;
        }
        
        try {
            // Try to get balance directly if connection is available
            if (solanaWallet.connection) {
                const balance = await solanaWallet.connection.getBalance(
                    new solanaWeb3.PublicKey(solanaWallet.publicKey)
                );
                solanaWallet.balance = balance / 1000000000; // Convert lamports to SOL
                updateBalanceUI(solanaWallet.balance);
            }
            
            // Also get balance from server (this ensures it's in sync with the database)
            $.ajax({
                url: vortexSolana.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_solana_balance',
                    wallet_address: solanaWallet.publicKey,
                    nonce: vortexSolana.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        updateBalanceUI(response.data.balance, response.data.formatted_balance);
                    }
                },
                error: function(error) {
                    console.error('Error getting balance:', error);
                }
            });
        } catch (error) {
            console.error('Error getting balance:', error);
        }
    }

    /**
     * Send SOL to another address
     * 
     * @param {string} recipientAddress - Recipient's Solana address
     * @param {number} amount - Amount to send in SOL
     * @returns {Promise<string>} - Transaction signature
     */
    async function sendSolana(recipientAddress, amount) {
        if (!solanaWallet.connected || !solanaWallet.publicKey || !solanaWallet.adapter) {
            throw new Error('Wallet not connected');
        }
        
        try {
            // Create a new transaction
            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.SystemProgram.transfer({
                    fromPubkey: new solanaWeb3.PublicKey(solanaWallet.publicKey),
                    toPubkey: new solanaWeb3.PublicKey(recipientAddress),
                    lamports: amount * 1000000000 // Convert SOL to lamports
                })
            );
            
            // Get the recent blockhash
            const { blockhash } = await solanaWallet.connection.getRecentBlockhash();
            transaction.recentBlockhash = blockhash;
            transaction.feePayer = new solanaWeb3.PublicKey(solanaWallet.publicKey);
            
            // Sign and send the transaction
            const signed = await solanaWallet.adapter.signTransaction(transaction);
            const signature = await solanaWallet.connection.sendRawTransaction(signed.serialize());
            
            // Wait for confirmation
            await solanaWallet.connection.confirmTransaction(signature);
            
            // Record transaction in our database
            recordTransaction(solanaWallet.publicKey, recipientAddress, amount, signature);
            
            return signature;
        } catch (error) {
            console.error('Error sending SOL:', error);
            throw error;
        }
    }

    /**
     * Record transaction in the database
     * 
     * @param {string} fromAddress - Sender's address
     * @param {string} toAddress - Recipient's address
     * @param {number} amount - Amount sent in SOL
     * @param {string} signature - Transaction signature
     */
    function recordTransaction(fromAddress, toAddress, amount, signature) {
        $.ajax({
            url: vortexSolana.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_process_transaction',
                from_address: fromAddress,
                to_address: toAddress,
                amount: amount,
                transaction_data: {
                    signature: signature,
                    timestamp: Date.now()
                },
                nonce: vortexSolana.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Transaction recorded:', response.data);
                }
            },
            error: function(error) {
                console.error('Error recording transaction:', error);
            }
        });
    }

    /**
     * Update wallet UI after connection
     */
    function updateWalletUI() {
        if (solanaWallet.connected && solanaWallet.publicKey) {
            $('.vortex-solana-wallet-connect').hide();
            $('.vortex-solana-wallet-info').show();
            
            // Format address for display
            const shortAddress = solanaWallet.publicKey.substring(0, 6) + '...' + 
                                  solanaWallet.publicKey.substring(solanaWallet.publicKey.length - 4);
            
            $('.vortex-solana-wallet-address .value').text(shortAddress);
            $('.vortex-copy-address-button').data('address', solanaWallet.publicKey);
            
            // Show send and transaction sections
            $('.vortex-solana-wallet-send').show();
            $('.vortex-solana-wallet-transactions').show();
        } else {
            $('.vortex-solana-wallet-connect').show();
            $('.vortex-solana-wallet-info').hide();
            $('.vortex-solana-wallet-send').hide();
            $('.vortex-solana-wallet-transactions').hide();
        }
    }

    /**
     * Update balance display in UI
     * 
     * @param {number} balance - Balance in SOL
     * @param {string} formattedBalance - Optional formatted balance string
     */
    function updateBalanceUI(balance, formattedBalance = null) {
        if (!formattedBalance) {
            formattedBalance = balance.toFixed(4) + ' SOL';
        }
        
        $('.vortex-solana-wallet-balance .value').text(formattedBalance);
        $('.vortex-solana-balance-amount').text(formattedBalance);
    }

    /**
     * Copy text to clipboard
     * 
     * @param {string} text - Text to copy
     */
    function copyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }

})(jQuery); 