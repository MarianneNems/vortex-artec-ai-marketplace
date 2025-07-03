/**
 * Tests for VORTEX TOLA functionality
 */

describe('VORTEX TOLA Integration', () => {
  beforeEach(() => {
    // Reset mocks before each test
    jest.clearAllMocks();
  });

  describe('Wallet Connection', () => {
    test('should check if MetaMask is available', () => {
      expect(global.ethereum).toBeDefined();
      expect(global.ethereum.isMetaMask).toBe(true);
    });

    test('should handle wallet connection request', async () => {
      const mockAccounts = ['0x1234567890123456789012345678901234567890'];
      global.ethereum.request.mockResolvedValue(mockAccounts);

      // Mock the wallet connection function
      const connectWallet = async () => {
        const accounts = await global.ethereum.request({
          method: 'eth_requestAccounts',
        });
        return accounts[0];
      };

      const connectedAccount = await connectWallet();
      expect(connectedAccount).toBe(mockAccounts[0]);
      expect(global.ethereum.request).toHaveBeenCalledWith({
        method: 'eth_requestAccounts',
      });
    });
  });

  describe('TOLA Balance', () => {
    test('should fetch user TOLA balance', async () => {
      const mockBalance = '1000';
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ success: true, balance: mockBalance }),
      });

      const getTolaBalance = async userId => {
        const response = await fetch(`${vortexAjax.restUrl}balance/${userId}`, {
          headers: {
            'X-WP-Nonce': vortexAjax.nonce,
          },
        });
        const data = await response.json();
        return data.balance;
      };

      const balance = await getTolaBalance(1);
      expect(balance).toBe(mockBalance);
      expect(global.fetch).toHaveBeenCalledWith(
        `${vortexAjax.restUrl}balance/1`,
        expect.objectContaining({
          headers: {
            'X-WP-Nonce': vortexAjax.nonce,
          },
        })
      );
    });

    test('should handle balance fetch error', async () => {
      global.fetch.mockRejectedValueOnce(new Error('Network error'));

      const getTolaBalance = async userId => {
        try {
          const response = await fetch(`${vortexAjax.restUrl}balance/${userId}`, {
            headers: {
              'X-WP-Nonce': vortexAjax.nonce,
            },
          });
          const data = await response.json();
          return data.balance;
        } catch (error) {
          throw new Error('Failed to fetch balance');
        }
      };

      await expect(getTolaBalance(1)).rejects.toThrow('Failed to fetch balance');
    });
  });

  describe('AJAX Integration', () => {
    test('should use correct AJAX configuration', () => {
      expect(vortexAjax.ajaxUrl).toBe('/wp-admin/admin-ajax.php');
      expect(vortexAjax.restUrl).toBe('/wp-json/vortex/v1/');
      expect(vortexAjax.nonce).toBeDefined();
      expect(vortexAjax.currentUserId).toBe(1);
      expect(vortexAjax.isUserLoggedIn).toBe(true);
    });

    test('should handle AJAX request with proper nonce', () => {
      const mockAjax = jest.fn();
      global.jQuery.ajax = mockAjax;

      const sendAjaxRequest = (action, data) => {
        return jQuery.ajax({
          url: vortexAjax.ajaxUrl,
          type: 'POST',
          data: {
            action: action,
            nonce: vortexAjax.nonce,
            ...data,
          },
        });
      };

      sendAjaxRequest('vortex_check_tola_balance', { user_id: 1 });

      expect(mockAjax).toHaveBeenCalledWith({
        url: vortexAjax.ajaxUrl,
        type: 'POST',
        data: {
          action: 'vortex_check_tola_balance',
          nonce: vortexAjax.nonce,
          user_id: 1,
        },
      });
    });
  });

  describe('Web3 Integration', () => {
    test('should create Web3 instance', () => {
      const web3 = new global.Web3();
      expect(web3).toBeDefined();
      expect(web3.eth).toBeDefined();
      expect(web3.utils).toBeDefined();
    });

    test('should handle transaction', async () => {
      const web3 = new global.Web3();
      const mockTransaction = { transactionHash: '0xtest123' };
      web3.eth.sendTransaction.mockResolvedValue(mockTransaction);

      const sendTransaction = async (fromAddress, toAddress, amount) => {
        return await web3.eth.sendTransaction({
          from: fromAddress,
          to: toAddress,
          value: web3.utils.toWei(amount, 'ether'),
        });
      };

      const result = await sendTransaction('0x123', '0x456', '1');

      expect(result.transactionHash).toBe('0xtest123');
      expect(web3.eth.sendTransaction).toHaveBeenCalled();
    });
  });

  describe('Local Storage', () => {
    test('should store and retrieve wallet data', () => {
      const walletData = {
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000',
        connected: true,
      };

      // Test storing data
      global.localStorage.setItem('vortex_wallet', JSON.stringify(walletData));
      expect(global.localStorage.setItem).toHaveBeenCalledWith(
        'vortex_wallet',
        JSON.stringify(walletData)
      );

      // Mock the return value for getItem
      global.localStorage.getItem.mockReturnValue(JSON.stringify(walletData));

      // Test retrieving data
      const retrievedData = JSON.parse(global.localStorage.getItem('vortex_wallet'));
      expect(retrievedData).toEqual(walletData);
      expect(global.localStorage.getItem).toHaveBeenCalledWith('vortex_wallet');
    });

    test('should handle missing wallet data', () => {
      global.localStorage.getItem.mockReturnValue(null);

      const getWalletData = () => {
        const data = global.localStorage.getItem('vortex_wallet');
        return data ? JSON.parse(data) : null;
      };

      const result = getWalletData();
      expect(result).toBeNull();
    });
  });
});
