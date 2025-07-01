# TOLA Whitepaper
**Vortex Artec AI & Marketplace**

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Market Challenges](#market-challenges)
3. [Our Solution](#our-solution)
4. [Token Overview](#token-overview)
5. [Fee Structure](#fee-structure)
6. [Distribution & Vesting](#distribution-vesting)
7. [Investor Offering & Roadmap](#investor-offering-roadmap)
8. [Incentives for Creators & Collectors](#incentives-for-creators-collectors)
9. [Deflation & Scarcity](#deflation-scarcity)
10. [Technical Integration](#technical-integration)
11. [Governance & Compliance](#governance-compliance)
12. [Roadmap Summary](#roadmap-summary)

---

## 1. Executive Summary

Vortex Artec AI Marketplace uses one token, TOLA, for all art sales, rewards, and community decisions. We automate fee splits on the blockchain, supporting artists, funding platform growth, and rewarding creators. This paper explains how TOLA works, how we'll raise funds, reward users, and grow value.

## 2. Market Challenges

- **Unclear Royalties**: Artists often don't earn from resales.
- **Hidden Fees**: Platforms take variable cuts behind the scenes.
- **Complex Payments**: Multiple tokens confuse buyers and sellers.

These issues slow adoption and weaken trust.

## 3. Our Solution

- **One Token**: TOLA handles payments, rewards, governance.
- **Automated Splits**: Smart contracts divide sale funds fairly.
- **Stable Purchases**: We accept USDC for art buys (1 USDC = price).
- **Community Control**: TOLA holders vote on features and fees.

## 4. Token Overview

- **Name**: Token of Love & Appreciation (TOLA)
- **Platform**: Solana blockchain
- **Token Standard**: SPL (Solana Program Library)
- **Token Contract Address**: H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky
- **Total Supply**: 50,000,000 TOLA (fixed)
- **Use Cases**: Sales, rewards, staking, governance.

## 5. Fee Structure

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

## 6. Distribution & Vesting

| Category | % of 50M | Amount | Vesting Schedule |
|----------|----------|--------|------------------|
| Community & Incentives | 10% | 5,000,000 | Ongoing reward campaigns |
| Artist Royalties Pool | 15% | 7,500,000 | Paid automatically on resales |
| DAO Treasury | 5% | 2,500,000 | Grants and development |
| Team & Operations | 60% | 30,000,000 | 6-mo cliff, 48-mo monthly vest |
| Strategic Partners | 5% | 2,500,000 | 6-mo cliff, 12-mo monthly vest |
| Creator Reserve | 5% | 2,500,000 | 12-mo cliff, 24-mo quarterly |

## 7. Investor Offering & Roadmap

We plan to raise $1.5M by selling 2.5M TOLA at $0.60 each. Below is a detailed, two-part plan for business and development teams.

### Business Steps

**Weeks 1–2: Finalize Terms & Launch KYC**
- Define Sale Terms: set price ($0.60/TOLA), maximum per investor, vesting (6‑month cliff, 12‑month unlock).
- Prepare Legal Documents: draft token purchase agreement and investor deck.
- KYC Integration: choose a trusted identity provider (e.g. Jumio, Onfido).
- Customer Portal Setup: enable investor sign-up, document upload, and approval workflow.

**Weeks 3–4: Deploy & Audit Sale Contract**
- Smart Contract Deployment: launch the investor-sale contract on Devnet.
- Security Audit: engage an audit firm to review fee and vesting logic.
- Test Sale Flow: execute test purchases, simulate KYC approvals, and vesting triggers.

**Weeks 5–6: Conduct Token Sale**
- Open Sale: publicize to angel investors, VCs, and community.
- Monitor Subscriptions: track USDC inflows and TOLA allocations.
- Finalize Raise: aim for $1.5M by distributing 2.5M TOLA.

**Weeks 7–8: Lock Tokens & Seed Liquidity**
- Lock Investor TOLA: move purchased tokens into a vesting contract.
- Seed DEX Pool: provide 250K USDC + 250K TOLA on Raydium or Serum.
- Apply for Listings: submit documentation to CEXs and DEX aggregators.

### Developer Tasks
- **KYC Portal**: integrate API from identity provider, build front-end forms, store approvals securely.
- **Sale Smart Contract**: implement buyTola() with price, vesting schedule, and USDC deposit logic.
- **Audit Preparation**: write unit tests for edge cases, simulate high-volume purchases.
- **Vesting Contract**: configure cliff and unlock logic, test time-based releases.
- **Liquidity Seeder Script**: automate supply of USDC and TOLA to liquidity pool, monitor pool health.
- **Exchange Integration**: build connectors for real-time price feeds and withdrawal endpoints.

## 8. Incentives for Creators & Collectors

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
- **1–3% Cashback** in TOLA on every purchase.
- **VIP Staking**: lock 5,000 TOLA for early access.
- **Quarterly Airdrops** based on TOLA holdings.

### Casual & Daily Users:
- **Micro‑Staking**: stake 50 TOLA, earn 5 TOLA per day.
- **Referral Bonus**: earn 50 TOLA for each new user.
- **Event Entry**: burn 50 TOLA to join exclusive raffles.

### 8.1 Subscription Tiers

Users and institutions can subscribe to our platform plans, paying in USDC or TOLA (1 TOLA = 1 USDC):

| Plan Name | Price (USDC/TOLA) | Benefits |
|-----------|-------------------|----------|
| Standard | 19 | Basic analytics, up to 100 uploads/month |
| Essential | 49 | Advanced analytics, up to 500 uploads, priority support |
| Premium | 99 | Full analytics, unlimited uploads, dedicated manager |

Subscribers receive platform credits, early drop access, and reduced transaction fees.

## 9. Deflation & Scarcity

To increase value, we burn tokens over time:
- **Optional Fee Burns**: burn a portion of platform fees.
- **Unclaimed Rewards**: expire and burn old bonuses weekly.
- **Event Burns**: require small burns (50 TOLA) for premium events.

### Supply Forecast:

| Month | Start | Burned | End |
|-------|-------|--------|-----|
| 1 | 50,000,000 | 50,000 | 49,950,000 |
| 6 | 49,700,000 | 250,000 | 49,450,000 |
| 12 | 49,200,000 | 500,000 | 48,700,000 |

## 10. Technical Integration

- **Blockchain**: Solana for fast, low-cost transactions
- **Contracts**: Rust-based, security audited
- **APIs & SDKs**: REST endpoints and JavaScript libraries
- **Wallets Supported**: Phantom, Sollet, Ledger, etc.

### Pseudocode for Sales Logic

```rust
fn buy_art(
    buyer: Pubkey,
    seller: Pubkey,
    artist: Pubkey,
    price: u64,             // in TOLA units
    is_primary: bool        // true for AI mint, false for resale
) {
    let mut remaining = price;

    // 1) Vortex Creator Royalty (5%) on all sales
    let creator_fee = price * 5 / 100;
    spl_token::transfer(buyer, vortex_creator_account, creator_fee)?;
    remaining -= creator_fee;

    // 2) Platform Commission (15%) on all sales
    let platform_fee = price * 15 / 100;
    spl_token::transfer(buyer, treasury_account, platform_fee)?;
    remaining -= platform_fee;

    if is_primary {
        // 3a) Primary sale: remainder goes to artist
        spl_token::transfer(buyer, artist, remaining)?;
    } else {
        // 3b) Secondary sale: 15% royalty to original artist
        let artist_royalty = price * 15 / 100;
        spl_token::transfer(buyer, artist, artist_royalty)?;
        remaining -= artist_royalty;

        // 4) Remainder to current seller
        spl_token::transfer(buyer, seller, remaining)?;
    }

    // 5) Mint or transfer NFT to buyer
    mint_or_transfer_nft(buyer, &art_token)?;
}
```

Deploy and test on Devnet, then audit before mainnet launch.

## 11. Optional Future DAO Governance

We have designed a Decentralized Autonomous Organization (DAO) that can be activated once the platform matures. This is optional at launch and can be implemented later to avoid complexity for early investors.

### Future DAO Features:
- **Proposal Rights**: Holders with ≥10% TOLA to submit platform improvement proposals.
- **Voting Quorum**: ≥20% participation; simple majority to decide.
- **On-Chain Transparency**: All DAO actions publicly auditable.
- **Multi-Sig Admin**: 2-of-3 signers required for contract upgrades.

### Compliance & Controls:
- **KYC/AML**: required for investor sale and large deposits.
- **Token Classification**: TOLA remains a utility/incentive token, non-security.
- **Data Privacy**: GDPR & CCPA compliance.

DAO governance can be launched in a later phase to keep the initial sale and roadmap straightforward.

**DAO Voting**: ≥10% TOLA to propose changes; ≥20% to vote.
**Multi‑Sig Control**: 2‑of‑3 required for upgrades.
**KYC/AML**: for investor sale and large USDC inflows.
**Privacy**: GDPR & CCPA compliant.

## 12. Roadmap Summary

Below is our phased plan, with clear examples of what each group can do and expect:

| Phase | Timeline | Key Outcome | Example for Users |
|-------|----------|-------------|-------------------|
| Supply Cleanup | Week 1 | Burn to 50M, update docs | Developers run the SPL burn command; everyone sees supply drop to 50M on Solscan. |
| Token Sale | Weeks 2–6 | Raise $1.5M, lock tokens | Investors purchase TOLA at $0.60; see vesting schedule in portal. |
| Liquidity | Weeks 7–8 | Seed pools, get listings | Developers deploy liquidity script; traders trade TOLA/USDC on Raydium. |
| Incentives | Months 1–3 | Launch rewards programs | Artists earn TOLA for uploading art; collectors get cashback in TOLA. |
| Growth | Months 3–12 | Partnerships & drops | Institutions use TOLA for VIP access; ambassadors run high-profile NFT drops. |

Each phase comes with tutorials and hands-on guides so everyone—developers, investors, artists, and casual users—knows exactly what to do and what to expect next.

---

## 14. Contact Information

**Marianne Nems**  
Founder & CEO, VORTEX ARTEC AI Marketplace  
Email: marianne@vortexartec.com  
Website: www.vortexartec.com  

---

© 2025 Vortex Artec Corp. All Rights Reserved. 