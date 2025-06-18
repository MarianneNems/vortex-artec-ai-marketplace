/**
 * Vortex TOLA Token Integration JavaScript
 *
 * Handles Solana wallet integration for TOLA tokens, including connection, transactions and balance checks.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    // Store connection state
    let tolaWallet = {
        connected: false,
        publicKey: null,
        connection: null,
        adapter: null,
        balance: 0,
        tokenMint: null,
        tokenProgramId: null,
        associatedTokenProgramId: null
    };

    // Initialize when document is ready
    $(document).ready(function() {
        initTola();
        setupEventListeners();
    });

    /**
     * Initialize Solana connection and TOLA token
     */
    async function initTola() {
        try {
            // Initialize connection to Solana network
            tolaWallet.connection = new solanaWeb3.Connection(vortexTola.rpcUrl, 'confirmed');
            
            // Set TOLA token address
            if (vortexTola.tokenAddress) {
                tolaWallet.tokenMint = new solanaWeb3.PublicKey(vortexTola.tokenAddress);
                
                // SPL Token program ID - standard for all tokens
                tolaWallet.tokenProgramId = new solanaWeb3.PublicKey("TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA");
                
                // Associated Token Account Program ID - standard for all tokens
                tolaWallet.associatedTokenProgramId = new solanaWeb3.PublicKey("ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL");
            } else {
                console.error('TOLA token address not configured');
            }
            
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
            console.error('Error initializing TOLA connection:', error);
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

        // Send TOLA form
        $(document).on('submit', '.vortex-tola-send-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $result = $form.find('.vortex-tola-send-result');
            
            const recipientAddress = $form.find('#recipient_address').val();
            const amount = parseFloat($form.find('#amount').val());
            
            if (!recipientAddress || isNaN(amount) || amount <= 0) {
                $result.html('<div class="error">Please enter a valid recipient and amount.</div>');
                return;
            }
            
            // Disable form while processing
            $form.find('button').prop('disabled', true).text('Processing...');
            $result.html('<div class="processing">Processing transaction...</div>');
            
            sendTola(recipientAddress, amount)
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
            tolaWallet.publicKey = resp.publicKey.toString();
            tolaWallet.connected = true;
            tolaWallet.adapter = provider;
            
            // Store address in localStorage for reconnection
            localStorage.setItem('vortex_wallet_address', tolaWallet.publicKey);
            
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
            if (tolaWallet.adapter && tolaWallet.adapter.disconnect) {
                await tolaWallet.adapter.disconnect();
            }
            
            // Reset wallet state
            tolaWallet.connected = false;
            tolaWallet.publicKey = null;
            localStorage.removeItem('vortex_wallet_address');
            
            // Send disconnect request to server
            $.ajax({
                url: vortexTola.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_disconnect_wallet',
                    nonce: vortexTola.nonce
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
     * Get TOLA token balance for connected wallet
     */
    async function getBalance() {
        if (!tolaWallet.connected || !tolaWallet.publicKey) {
            return;
        }
        
        try {
            // Get TOLA balance from server (this handles the token account lookup)
            $.ajax({
                url: vortexTola.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vortex_get_tola_balance',
                    wallet_address: tolaWallet.publicKey,
                    nonce: vortexTola.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        tolaWallet.balance = response.data.balance;
                        updateBalanceUI(response.data.balance, response.data.formatted_balance);
                    }
                },
                error: function(error) {
                    console.error('Error getting TOLA balance:', error);
                }
            });
        } catch (error) {
            console.error('Error getting TOLA balance:', error);
        }
    }

    /**
     * Find or create an associated token account for a wallet
     * 
     * @param {string} walletAddress - Wallet to find/create token account for
     * @returns {Promise<string>} - Associated token account address
     */
    async function findOrCreateAssociatedTokenAccount(walletAddress) {
        if (!tolaWallet.tokenMint || !tolaWallet.tokenProgramId || !tolaWallet.associatedTokenProgramId) {
            throw new Error('TOLA token not properly configured');
        }
        
        const walletPubkey = new solanaWeb3.PublicKey(walletAddress);
        
        // Find the associated token account address
        const [associatedTokenAddress] = await solanaWeb3.PublicKey.findProgramAddress(
            [
                walletPubkey.toBuffer(),
                tolaWallet.tokenProgramId.toBuffer(),
                tolaWallet.tokenMint.toBuffer(),
            ],
            tolaWallet.associatedTokenProgramId
        );
        
        // Check if the associated token account exists
        const tokenAccount = await tolaWallet.connection.getAccountInfo(associatedTokenAddress);
        
        if (tokenAccount === null) {
            // Token account doesn't exist, create it
            const transaction = new solanaWeb3.Transaction();
            
            // Create the associated token account
            transaction.add(
                new solanaWeb3.TransactionInstruction({
                    keys: [
                        { pubkey: tolaWallet.publicKey, isSigner: true, isWritable: true },
                        { pubkey: associatedTokenAddress, isSigner: false, isWritable: true },
                        { pubkey: walletPubkey, isSigner: false, isWritable: false },
                        { pubkey: tolaWallet.tokenMint, isSigner: false, isWritable: false },
                        { pubkey: solanaWeb3.SystemProgram.programId, isSigner: false, isWritable: false },
                        { pubkey: tolaWallet.tokenProgramId, isSigner: false, isWritable: false },
                        { pubkey: solanaWeb3.SYSVAR_RENT_PUBKEY, isSigner: false, isWritable: false },
                    ],
                    programId: tolaWallet.associatedTokenProgramId,
                    data: Buffer.from([]),
                })
            );
            
            // Get the recent blockhash
            const { blockhash } = await tolaWallet.connection.getRecentBlockhash();
            transaction.recentBlockhash = blockhash;
            transaction.feePayer = new solanaWeb3.PublicKey(tolaWallet.publicKey);
            
            // Sign and send the transaction
            const signed = await tolaWallet.adapter.signTransaction(transaction);
            const signature = await tolaWallet.connection.sendRawTransaction(signed.serialize());
            
            // Wait for confirmation
            await tolaWallet.connection.confirmTransaction(signature);
        }
        
        return associatedTokenAddress.toString();
    }

    /**
     * Send TOLA tokens to another address
     * 
     * @param {string} recipientAddress - Recipient's Solana address
     * @param {number} amount - Amount to send in TOLA
     * @returns {Promise<string>} - Transaction signature
     */
    async function sendTola(recipientAddress, amount) {
        if (!tolaWallet.connected || !tolaWallet.publicKey || !tolaWallet.adapter) {
            throw new Error('Wallet not connected');
        }
        
        if (!tolaWallet.tokenMint) {
            throw new Error('TOLA token not configured');
        }
        
        try {
            // Convert amount to token units
            const tokenAmount = amount * Math.pow(10, vortexTola.tokenDecimals);
            
            // Find or create sender's token account
            const senderTokenAccount = await findOrCreateAssociatedTokenAccount(tolaWallet.publicKey);
            
            // Find or create recipient's token account
            const recipientTokenAccount = await findOrCreateAssociatedTokenAccount(recipientAddress);
            
            // Create a new transaction
            const transaction = new solanaWeb3.Transaction().add(
                splToken.Token.createTransferInstruction(
                    tolaWallet.tokenProgramId,
                    new solanaWeb3.PublicKey(senderTokenAccount),
                    new solanaWeb3.PublicKey(recipientTokenAccount),
                    new solanaWeb3.PublicKey(tolaWallet.publicKey),
                    [],
                    tokenAmount
                )
            );
            
            // Get the recent blockhash
            const { blockhash } = await tolaWallet.connection.getRecentBlockhash();
            transaction.recentBlockhash = blockhash;
            transaction.feePayer = new solanaWeb3.PublicKey(tolaWallet.publicKey);
            
            // Sign and send the transaction
            const signed = await tolaWallet.adapter.signTransaction(transaction);
            const signature = await tolaWallet.connection.sendRawTransaction(signed.serialize());
            
            // Wait for confirmation
            await tolaWallet.connection.confirmTransaction(signature);
            
            // Record transaction in our database
            recordTransaction(tolaWallet.publicKey, recipientAddress, amount, signature);
            
            return signature;
        } catch (error) {
            console.error('Error sending TOLA:', error);
            throw error;
        }
    }

    /**
     * Record transaction in the database
     * 
     * @param {string} fromAddress - Sender's address
     * @param {string} toAddress - Recipient's address
     * @param {number} amount - Amount sent in TOLA
     * @param {string} signature - Transaction signature
     */
    function recordTransaction(fromAddress, toAddress, amount, signature) {
        $.ajax({
            url: vortexTola.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_process_tola_transaction',
                from_address: fromAddress,
                to_address: toAddress,
                amount: amount,
                transaction_data: {
                    signature: signature,
                    token_mint: tolaWallet.tokenMint.toString(),
                    timestamp: Date.now()
                },
                nonce: vortexTola.nonce
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
        if (tolaWallet.connected && tolaWallet.publicKey) {
            $('.vortex-tola-wallet-connect').hide();
            $('.vortex-tola-wallet-info').show();
            
            // Format address for display
            const shortAddress = tolaWallet.publicKey.substring(0, 6) + '...' + 
                                 tolaWallet.publicKey.substring(tolaWallet.publicKey.length - 4);
            
            $('.vortex-tola-wallet-address .value').text(shortAddress);
            $('.vortex-copy-address-button').data('address', tolaWallet.publicKey);
            
            // Show send and transaction sections
            $('.vortex-tola-wallet-send').show();
            $('.vortex-tola-wallet-transactions').show();
        } else {
            $('.vortex-tola-wallet-connect').show();
            $('.vortex-tola-wallet-info').hide();
            $('.vortex-tola-wallet-send').hide();
            $('.vortex-tola-wallet-transactions').hide();
        }
    }

    /**
     * Update balance display in UI
     * 
     * @param {number} balance - Balance in TOLA
     * @param {string} formattedBalance - Optional formatted balance string
     */
    function updateBalanceUI(balance, formattedBalance = null) {
        if (!formattedBalance) {
            formattedBalance = balance.toFixed(4) + ' TOLA';
        }
        
        $('.vortex-tola-wallet-balance .value').text(formattedBalance);
        $('.vortex-tola-balance-amount').text(formattedBalance);
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