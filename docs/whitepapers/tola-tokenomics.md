# TOLA Token Economics Whitepaper

## Executive Summary
TOLA (Token of Love and Appreciation) is the native utility token of the VORTEX AI Marketplace, designed to facilitate transactions, reward participation, and enable governance within the ecosystem. This document outlines the tokenomics, distribution, and utility of the TOLA token.

## 1. Token Overview

### 1.1 Token Details
- **Name**: TOLA (Token of Love and Appreciation)
- **Symbol**: TOLA
- **Network**: Solana
- **Token Standard**: SPL (Solana Program Library)
- **Token Contract Address**: H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky
- **Decimals**: 9
- **Total Supply**: 50,000,000 TOLA (fixed)
- **Original Supply**: 1,000,000,000 TOLA
- **Burned Amount**: 950,000,000 TOLA
- **Initial Price**: $0.60 USD

### 1.2 Token Purpose
- Marketplace transactions
- Platform governance
- Reward distribution
- Staking mechanism
- Premium features access

## 2. Token Distribution

### 2.1 Current Distribution (50M TOLA)
- **Community & Incentives**: 10% (5,000,000 TOLA)
- **Artist Royalties Pool**: 15% (7,500,000 TOLA)
- **DAO Treasury**: 5% (2,500,000 TOLA)
- **Team & Operations**: 60% (30,000,000 TOLA)
- **Strategic Partners**: 5% (2,500,000 TOLA)
- **Creator Reserve**: 5% (2,500,000 TOLA)

### 2.2 Vesting Schedule
- **Community & Incentives**: Ongoing reward campaigns
- **Artist Royalties Pool**: Paid automatically on resales
- **DAO Treasury**: Grants and development
- **Team & Operations**: 6-month cliff, 48-month monthly vest
- **Strategic Partners**: 6-month cliff, 12-month monthly vest
- **Creator Reserve**: 12-month cliff, 24-month quarterly vest

## 3. Fee Structure

### 3.1 Primary AI Mint (Generative Art) - 100 TOLA
- **5% (5 TOLA)** → Vortex Creator (founder royalty)
- **15% (15 TOLA)** → Platform Treasury (operations)
- **80% (80 TOLA)** → Artist (initial creator)

### 3.2 Secondary Resale - 100 TOLA
- **5% (5 TOLA)** → Vortex Creator (founder royalty on resales)
- **15% (15 TOLA)** → Original Artist (secondary royalty)
- **15% (15 TOLA)** → Platform Treasury (operations)
- **65% (65 TOLA)** → Current Seller (owner of NFT)

*All splits execute instantly in the smart contract upon each sale.*

## 4. Token Utility

### 4.1 Platform Usage
- **Artwork Purchases**: Primary currency for artwork transactions
- **Commission Payments**: Platform fee payments
- **Premium Features**: Access to advanced features
- **Subscription Services**: Premium subscription payments

### 4.2 Governance Rights
- **Voting Power**: 1 TOLA = 1 vote
- **Proposal Creation**: 100,000 TOLA required
- **Governance Parameters**:
  - Minimum voting period: 3 days
  - Quorum requirement: 5% of circulating supply
  - Execution delay: 24 hours

### 4.3 Staking Benefits
- **Staking Rewards**: 5-15% APY
- **Governance Rights**: Enhanced voting power
- **Platform Benefits**: Reduced fees, priority access
- **VIP Staking**: 5,000 TOLA minimum for early access

## 5. Incentives for Creators & Collectors

### 5.1 Artist Rewards (5M TOLA pool)
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

### 5.2 Collector Perks
- **1–3% Cashback** in TOLA on every purchase
- **VIP Staking**: lock 5,000 TOLA for early access
- **Quarterly Airdrops** based on TOLA holdings

### 5.3 Casual & Daily Users
- **Micro‑Staking**: stake 50 TOLA, earn 5 TOLA per day
- **Referral Bonus**: earn 50 TOLA for each new user
- **Event Entry**: burn 50 TOLA to join exclusive raffles

## 6. Subscription Tiers

Users and institutions can subscribe to platform plans, paying in USDC or TOLA (1 TOLA = 1 USDC):

| Plan Name | Price (USDC/TOLA) | Benefits |
|-----------|-------------------|----------|
| Standard | 19 | Basic analytics, up to 100 uploads/month |
| Essential | 49 | Advanced analytics, up to 500 uploads, priority support |
| Premium | 99 | Full analytics, unlimited uploads, dedicated manager |

## 7. Investor Offering & Roadmap

### 7.1 Fundraising Plan
- **Target Raise**: $1.5M
- **Tokens for Sale**: 2.5M TOLA
- **Price per Token**: $0.60
- **Vesting Schedule**: 6-month cliff, 12-month unlock

### 7.2 Use of Funds
- **Development**: 40% - Platform development and AI integration
- **Marketing**: 30% - User acquisition and partnerships
- **Operations**: 20% - Team expansion and infrastructure
- **Reserve**: 10% - Contingency and future opportunities

## 8. Deflation & Scarcity

### 8.1 Token Burn Mechanisms
To increase value, we burn tokens over time:
- **Optional Fee Burns**: burn a portion of platform fees
- **Unclaimed Rewards**: expire and burn old bonuses weekly
- **Event Burns**: require small burns (50 TOLA) for premium events

### 8.2 Supply Forecast
| Month | Start Supply | Burned | End Supply |
|-------|-------------|--------|------------|
| 1 | 50,000,000 | 50,000 | 49,950,000 |
| 6 | 49,700,000 | 250,000 | 49,450,000 |
| 12 | 49,200,000 | 500,000 | 48,700,000 |

## 9. Technical Integration

### 9.1 Blockchain Infrastructure
- **Blockchain**: Solana for fast, low-cost transactions
- **Token Program**: SPL Token program
- **Programs**: Rust-based, security audited
- **APIs & SDKs**: JSON-RPC endpoints and JavaScript libraries
- **Wallets Supported**: Phantom, Solflare, Sollet, Ledger, etc.

### 9.2 Smart Contract Logic (Rust)
```rust
pub fn buy_art(
    ctx: Context<BuyArt>,
    price: u64,             // in TOLA units
    is_primary: bool        // true for AI mint, false for resale
) -> Result<()> {
    let mut remaining = price;

    // 1) Vortex Creator Royalty (5%) on all sales
    let creator_fee = price * 5 / 100;
    transfer_tola(&ctx, &ctx.accounts.vortex_creator, creator_fee)?;
    remaining -= creator_fee;

    // 2) Platform Commission (15%) on all sales
    let platform_fee = price * 15 / 100;
    transfer_tola(&ctx, &ctx.accounts.treasury, platform_fee)?;
    remaining -= platform_fee;

    if is_primary {
        // 3a) Primary sale: remainder goes to artist
        transfer_tola(&ctx, &ctx.accounts.artist, remaining)?;
    } else {
        // 3b) Secondary sale: 15% royalty to original artist
        let artist_royalty = price * 15 / 100;
        transfer_tola(&ctx, &ctx.accounts.artist, artist_royalty)?;
        remaining -= artist_royalty;

        // 4) Remainder to current seller
        transfer_tola(&ctx, &ctx.accounts.seller, remaining)?;
    }

    // 5) Mint or transfer NFT to buyer
    mint_or_transfer_nft(&ctx, &ctx.accounts.buyer)?;
    
    Ok(())
}
```

## 10. Governance & Compliance

### 10.1 DAO Governance (Future Implementation)
- **Proposal Rights**: Holders with ≥10% TOLA to submit platform improvement proposals
- **Voting Quorum**: ≥20% participation; simple majority to decide
- **On-Chain Transparency**: All DAO actions publicly auditable
- **Multi-Sig Admin**: 2-of-3 signers required for contract upgrades

### 10.2 Compliance & Controls
- **KYC/AML**: required for investor sale and large deposits
- **Token Classification**: TOLA remains a utility/incentive token, non-security
- **Data Privacy**: GDPR & CCPA compliance

## 11. Roadmap Summary

| Phase | Timeline | Key Outcome | Example for Users |
|-------|----------|-------------|-------------------|
| Supply Cleanup | Week 1 | Burn to 50M, update docs | Developers run the SPL burn command; everyone sees supply drop to 50M on Solscan |
| Token Sale | Weeks 2–6 | Raise $1.5M, lock tokens | Investors purchase TOLA at $0.60; see vesting schedule in portal |
| Liquidity | Weeks 7–8 | Seed pools, get listings | Developers deploy liquidity script; traders trade TOLA/USDC on Raydium |
| Incentives | Months 1–3 | Launch rewards programs | Artists earn TOLA for uploading art; collectors get cashback in TOLA |
| Growth | Months 3–12 | Partnerships & drops | Institutions use TOLA for VIP access; ambassadors run high-profile NFT drops |

## 12. Risk Analysis

### 12.1 Market Risks
- **Cryptocurrency volatility**: TOLA price may fluctuate with market conditions
- **Regulatory changes**: New regulations may impact token utility
- **Competition**: Other platforms may offer similar features

### 12.2 Technical Risks
- **Smart contract bugs**: Potential vulnerabilities in code
- **Solana network issues**: Downtime or congestion on the blockchain
- **Integration challenges**: Technical difficulties with platform features

### 12.3 Mitigation Strategies
- **Security audits**: Regular code reviews and testing
- **Diversification**: Multiple revenue streams and use cases
- **Community governance**: Decentralized decision-making process

## 13. Contact Information

**Marianne Nems**  
Founder & CEO, VORTEX ARTEC AI Marketplace  
Email: marianne@vortexartec.com  
Website: www.vortexartec.com  

---

© 2025 Vortex Artec Corp. All Rights Reserved. 