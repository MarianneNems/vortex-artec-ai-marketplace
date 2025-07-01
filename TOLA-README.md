# TOLA Token Integration Guide

![TOLA Token Logo](assets/images/tola-logo.png)

## Overview

TOLA (Token of Love and Appreciation) is a Solana-based utility token that powers the VORTEX AI Marketplace ecosystem. It enables a wide range of functionalities from purchases and transfers to governance and rewards, creating a complete token economy for the marketplace.

## Key Features

- **Native Solana Integration**: Built on the Solana blockchain for fast, low-cost transactions
- **SPL Token Standard**: Secure, efficient token standard for Solana
- **Smart Contract Powered**: Secure, transparent transactions using audited programs
- **Multi-utility Design**: TOLA serves multiple functions within the ecosystem
- **Real-time Metrics**: Comprehensive analytics on token usage and circulation
- **Staking Mechanism**: Stake tokens for platform benefits and yield
- **Governance Rights**: Token-weighted voting in the VORTEX DAO

## Token Details

- **Token Name**: TOLA (Token of Love and Appreciation)
- **Blockchain Network**: Solana
- **Token Standard**: SPL (Solana Program Library)
- **Token Contract Address**: H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky
- **Decimals**: 9
- **Total Supply**: 50,000,000 TOLA (fixed)
- **Original Supply**: 1,000,000,000 TOLA
- **Burned Amount**: 950,000,000 TOLA

## Token Utilities

### Primary Utilities

1. **Purchase Medium**
   - Buy artwork directly with TOLA
   - Access premium features and content
   - Subscribe to advanced AI generation capabilities
   - Receive discounts when using TOLA for purchases

2. **Reward Mechanism**
   - Earn TOLA for platform engagement
   - Receive TOLA for successful sales (artists)
   - Gain tokens through the gamification system
   - Get staking rewards for providing liquidity

3. **Governance Token**
   - Create and vote on platform proposals
   - Determine future features and parameters
   - Participate in treasury management decisions
   - Vote on featured artists and collections

4. **Staking Benefits**
   - Reduce marketplace fees
   - Access exclusive features and content
   - Increase voting power in governance
   - Earn passive income through staking rewards

## Fee Structure

### Primary AI Mint (Generative Art) – Buyer pays 100 TOLA:
- **5% (5 TOLA)** → Vortex Creator (founder royalty)
- **15% (15 TOLA)** → Platform Treasury (operations)
- **80% (80 TOLA)** → Artist (initial creator)

### Secondary Resale – Buyer pays 100 TOLA:
- **5% (5 TOLA)** → Vortex Creator (founder royalty on resales)
- **15% (15 TOLA)** → Original Artist (secondary royalty)
- **15% (15 TOLA)** → Platform Treasury (operations)
- **65% (65 TOLA)** → Current Seller (owner of NFT)

*All splits execute instantly in the smart contract upon each sale.*

## Token Distribution

- **Community & Incentives**: 5,000,000 TOLA (10%)
- **Artist Royalties Pool**: 7,500,000 TOLA (15%)
- **DAO Treasury**: 2,500,000 TOLA (5%)
- **Team & Operations**: 30,000,000 TOLA (60%) - 6-mo cliff, 48-mo monthly vest
- **Strategic Partners**: 2,500,000 TOLA (5%) - 6-mo cliff, 12-mo monthly vest
- **Creator Reserve**: 2,500,000 TOLA (5%) - 12-mo cliff, 24-mo quarterly

## Incentives & Rewards

### Artist Rewards (5M TOLA pool):
| Action | TOLA Award | Purpose |
|--------|------------|---------|
| Profile Setup | 500 | Quick onboarding bonus |
| Upload Artwork | 250 | Encourage new content |
| Publish Blog Post | 200 | Share insights and tips |
| Trade Artwork | 500 | Boost platform activity |
| Make a Sale | 700 | Reward revenue generation |
| Weekly Top 10 | 1,000 | Celebrate outstanding work |
| Refer an Artist | 1,000 | Grow the creator community |
| Refer a Collector | 1,000 | Expand the buyer base |

### Collector Perks:
- **1–3% Cashback** in TOLA on every purchase
- **VIP Staking**: lock 5,000 TOLA for early access
- **Quarterly Airdrops** based on TOLA holdings

### Casual & Daily Users:
- **Micro‑Staking**: stake 50 TOLA, earn 5 TOLA per day
- **Referral Bonus**: earn 50 TOLA for each new user
- **Event Entry**: burn 50 TOLA to join exclusive raffles

## Subscription Tiers

Users and institutions can subscribe to our platform plans, paying in USDC or TOLA (1 TOLA = 1 USDC):

| Plan Name | Price (USDC/TOLA) | Benefits |
|-----------|-------------------|----------|
| Standard | 19 | Basic analytics, up to 100 uploads/month |
| Essential | 49 | Advanced analytics, up to 500 uploads, priority support |
| Premium | 99 | Full analytics, unlimited uploads, dedicated manager |

## Deflation & Scarcity

To increase value, we burn tokens over time:
- **Optional Fee Burns**: burn a portion of platform fees
- **Unclaimed Rewards**: expire and burn old bonuses weekly
- **Event Burns**: require small burns (50 TOLA) for premium events

### Supply Forecast:
| Month | Start | Burned | End |
|-------|-------|--------|-----|
| 1 | 50,000,000 | 50,000 | 49,950,000 |
| 6 | 49,700,000 | 250,000 | 49,450,000 |
| 12 | 49,200,000 | 500,000 | 48,700,000 |

## Technical Integration

### Blockchain Infrastructure
- **Blockchain**: Solana for fast, low-cost transactions
- **Token Program**: SPL Token program
- **Programs**: Rust-based, security audited
- **APIs & SDKs**: JSON-RPC endpoints and JavaScript libraries
- **Wallets Supported**: Phantom, Solflare, Sollet, Ledger, etc.

### Integration Example

```javascript
// TOLA Token Integration
const { Connection, PublicKey, clusterApiUrl } = require('@solana/web3.js');
const { Token, TOKEN_PROGRAM_ID } = require('@solana/spl-token');

const TOLA_MINT = new PublicKey('H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky');
const connection = new Connection(clusterApiUrl('mainnet-beta'));

// Get TOLA balance
async function getTolaBalance(walletAddress) {
    const wallet = new PublicKey(walletAddress);
    const tokenAccounts = await connection.getTokenAccountsByOwner(wallet, {
        mint: TOLA_MINT
    });
    
    if (tokenAccounts.value.length > 0) {
        const accountInfo = await connection.getTokenAccountBalance(
            tokenAccounts.value[0].pubkey
        );
        return accountInfo.value.uiAmount;
    }
    return 0;
}
```

### Smart Contract Logic (Rust)

```rust
// Fee Distribution Logic
pub fn process_artwork_sale(
    ctx: Context<ProcessSale>,
    price: u64,
    is_primary: bool,
) -> Result<()> {
    let mut remaining = price;
    
    // 1) Vortex Creator Royalty (5%) on all sales
    let creator_fee = price.checked_mul(5).unwrap().checked_div(100).unwrap();
    transfer_tola(&ctx, &ctx.accounts.vortex_creator, creator_fee)?;
    remaining = remaining.checked_sub(creator_fee).unwrap();
    
    // 2) Platform Commission (15%) on all sales
    let platform_fee = price.checked_mul(15).unwrap().checked_div(100).unwrap();
    transfer_tola(&ctx, &ctx.accounts.treasury, platform_fee)?;
    remaining = remaining.checked_sub(platform_fee).unwrap();
    
    if is_primary {
        // 3a) Primary sale: remainder goes to artist
        transfer_tola(&ctx, &ctx.accounts.artist, remaining)?;
    } else {
        // 3b) Secondary sale: 15% royalty to original artist
        let artist_royalty = price.checked_mul(15).unwrap().checked_div(100).unwrap();
        transfer_tola(&ctx, &ctx.accounts.artist, artist_royalty)?;
        remaining = remaining.checked_sub(artist_royalty).unwrap();
        
        // 4) Remainder to current seller
        transfer_tola(&ctx, &ctx.accounts.seller, remaining)?;
    }
    
    Ok(())
}
```

## Wallet Integration

### Supported Wallets
- **Phantom**: Primary recommended wallet
- **Solflare**: Full-featured Solana wallet
- **Sollet**: Web-based wallet
- **Ledger**: Hardware wallet support
- **Glow**: Mobile-first wallet

### WordPress Integration

```php
// TOLA Balance Check
class Vortex_TOLA_Integration {
    private $mint_address = 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky';
    
    public function get_user_tola_balance($wallet_address) {
        $rpc_url = 'https://api.mainnet-beta.solana.com';
        
        $data = array(
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccountsByOwner',
            'params' => array(
                $wallet_address,
                array('mint' => $this->mint_address),
                array('encoding' => 'jsonParsed')
            )
        );
        
        $response = wp_remote_post($rpc_url, array(
            'body' => json_encode($data),
            'headers' => array('Content-Type' => 'application/json')
        ));
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['result']['value'][0])) {
            $token_amount = $body['result']['value'][0]['account']['data']['parsed']['info']['tokenAmount'];
            return floatval($token_amount['uiAmount']);
        }
        
        return 0;
    }
}
```

## Governance

### DAO Features (Future Implementation)
- **Proposal Rights**: Holders with ≥10% TOLA to submit platform improvement proposals
- **Voting Quorum**: ≥20% participation; simple majority to decide
- **On-Chain Transparency**: All DAO actions publicly auditable
- **Multi-Sig Admin**: 2-of-3 signers required for contract upgrades

### Compliance & Controls
- **KYC/AML**: required for investor sale and large deposits
- **Token Classification**: TOLA remains a utility/incentive token, non-security
- **Data Privacy**: GDPR & CCPA compliance

## Integration Testing

```bash
# Test TOLA integration
curl -X POST https://api.mainnet-beta.solana.com \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "getAccountInfo",
    "params": [
      "H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky",
      {"encoding": "jsonParsed"}
    ]
  }'
```

## Contact & Support

For integration support and technical questions:
- **GitHub**: https://github.com/MarianneNems/vortex-artec-ai-marketplace
- **Documentation**: https://docs.vortexartec.com
- **Email**: marianne@vortexartec.com

---

**© 2025 Vortex Artec Corp. All Rights Reserved.** 