# TOLA Token Supply Management

## Current Supply Status

- **Original Supply**: 1,000,000,000 TOLA
- **Burned Amount**: 950,000,000 TOLA
- **Current Total Supply**: 50,000,000 TOLA (fixed)

## Token Details

- **Token Name**: TOLA (Token of Love and Appreciation)
- **Blockchain Network**: Solana
- **Token Standard**: SPL (Solana Program Library)
- **Token Contract Address**: H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky
- **Decimals**: 9

## Burning History

The TOLA token supply was reduced from 1 billion to 50 million tokens through a strategic burn to increase scarcity and value. This burn was executed to align with the marketplace's growth strategy and to create a more valuable token ecosystem.

## Token Distribution (50M TOLA)

| Category | Percentage | Amount | Vesting Schedule |
|----------|------------|--------|------------------|
| Community & Incentives | 10% | 5,000,000 | Ongoing reward campaigns |
| Artist Royalties Pool | 15% | 7,500,000 | Paid automatically on resales |
| DAO Treasury | 5% | 2,500,000 | Grants and development |
| Team & Operations | 60% | 30,000,000 | 6-mo cliff, 48-mo monthly vest |
| Strategic Partners | 5% | 2,500,000 | 6-mo cliff, 12-mo monthly vest |
| Creator Reserve | 5% | 2,500,000 | 12-mo cliff, 24-mo quarterly |

## Deflation & Scarcity Strategy

To maintain and increase token value over time, we implement the following deflationary mechanisms:

1. **Optional Fee Burns**: A portion of platform fees are burned.
2. **Unclaimed Rewards**: Expired bonuses are burned weekly.
3. **Event Burns**: Premium events require small burns (50 TOLA).

### Supply Forecast:

| Month | Start Supply | Burned | End Supply |
|-------|-------------|--------|------------|
| 1     | 50,000,000  | 50,000 | 49,950,000 |
| 6     | 49,700,000  | 250,000| 49,450,000 |
| 12    | 49,200,000  | 500,000| 48,700,000 |

## Investor Offering

- **Planned Raise**: $1.5M
- **Tokens for Sale**: 2.5M TOLA
- **Price per Token**: $0.60
- **Vesting Schedule**: 6-month cliff, 12-month unlock

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

## Technical Integration

### Solana Integration
- **Blockchain**: Solana mainnet-beta
- **RPC Endpoint**: https://api.mainnet-beta.solana.com
- **Token Program**: SPL Token program
- **Programs**: Rust-based, security audited

### Supply Verification

You can verify the current TOLA supply on Solana:

```bash
# Check TOLA token info
curl -X POST https://api.mainnet-beta.solana.com \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "getTokenSupply",
    "params": [
      "H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky"
    ]
  }'
```

### Burn Verification

All token burns are recorded on-chain and can be verified using Solana explorers:
- **Solscan**: https://solscan.io/token/H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky
- **Solana Explorer**: https://explorer.solana.com/address/H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky

## Liquidity & Trading

### Planned Liquidity Pools
- **Raydium**: TOLA/USDC pool with 250K USDC + 250K TOLA
- **Serum**: Order book for advanced trading
- **CEX Listings**: Applications submitted to major exchanges

### Price Discovery
- **Initial Price**: $0.60 per TOLA (investor sale)
- **Market Price**: Determined by liquidity pools and trading
- **Price Oracles**: Integration with Pyth Network for real-time pricing

## Governance

### Future DAO Implementation
- **Proposal Rights**: ≥10% TOLA to submit proposals
- **Voting Quorum**: ≥20% participation required
- **Multi-Sig Control**: 2-of-3 signers for upgrades
- **Transparency**: All actions recorded on-chain

---

**Last Updated**: January 2025  
**Contract Address**: H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky  
**Network**: Solana Mainnet 