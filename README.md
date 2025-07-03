# VORTEX AI Marketplace

A blockchain-powered art marketplace with integrated AI agent orchestration, TOLA token functionality, and multi-agent AI art generation tools.

## Overview

VORTEX is a comprehensive AI-powered marketplace for digital art, featuring:

- **Multi-Agent AI System**: THORIUS, HURAII, CLOE, and Business Strategist agents
- **Blockchain Integration**: Solana-based TOLA token for transactions and rewards
- **Advanced Orchestration**: Intelligent query routing and collaborative responses
- **Artist Tools**: AI-assisted art generation and portfolio management
- **Collector Features**: Personalized recommendations and investment tracking

## AI Agents

### THORIUS (Ethical Concierge)

THORIUS serves as the central orchestrator for the VORTEX ecosystem, coordinating between specialized AI agents to provide optimal responses. It features:

- **Intelligent Query Routing**: Determines which agent can best handle a specific query
- **Collaborative Processing**: Combines insights from multiple agents for complex queries
- **Domain-Specific Refinement**: Tailors queries for each agent's expertise
- **Content Blending**: Sophisticated algorithms for merging responses from different agents
- **Security Governance**: Ensures ethical AI usage and user data protection

### HURAII (Artistic AI)

HURAII is the creative engine of VORTEX, specializing in art generation and style analysis:

- **Seed-Art Technique**: Generates unique artwork based on user prompts
- **Artistic DNA Mapping**: Analyzes and replicates artistic styles
- **Style Evolution Tracking**: Monitors development of artistic trends

### CLOE (Curation Engine)

CLOE provides personalized art discovery and market intelligence:

- **Personalization**: Tailors recommendations based on user preferences
- **Market Intelligence**: Analyzes trends and predicts emerging artists
- **Behavioral Analytics**: Understands user behavior to enhance recommendations

### Business Strategist (HORACE)

The Business Strategist agent offers financial and strategic guidance:

- **Portfolio Management**: Helps artists and collectors manage their portfolios
- **Growth Strategy**: Provides actionable insights for career development
- **Risk Assessment**: Evaluates investment opportunities and market risks

## TOLA Token Integration

VORTEX integrates the TOLA token, a Solana-based SPL token with 50M total supply:

- **Art Purchases**: Used for buying and selling artwork
- **Rewards System**: Earned through platform participation
- **Governance Voting**: Enables community decision-making
- **TOLA of the Day**: Daily AI artwork with community revenue sharing

## API Endpoints

### THORIUS API

```
POST /vortex/v1/thorius/query
```
Process a user query with optimal agent selection.

```
POST /vortex/v1/thorius/collaborative
```
Process a complex query using multiple agents collaboratively.

```
GET /vortex/v1/thorius/status
```
Get the status of all AI agents.

```
POST /vortex/v1/thorius/admin/query
```
Process an admin query with access to advanced analytics.

### HURAII API

```
POST /vortex-ai/v1/huraii/generate
```
Generate artwork based on user prompts.

```
GET /vortex-ai/v1/huraii/styles
```
Get available artistic styles.

```
GET /vortex-ai/v1/huraii/artists
```
Get available artist influences.

### CLOE API

```
GET /vortex-ai/v1/market-data
```
Get market overview data.

```
GET /vortex-ai/v1/market-trends
```
Get current market trends.

```
GET /vortex-ai/v1/artist-insights/{id}
```
Get insights for a specific artist.

## Production Installation

### Prerequisites

- **WordPress**: 5.6 or higher
- **PHP**: 8.1 or higher
- **Node.js**: 18+ (for frontend build)
- **Python**: 3.9+ (for AI server)
- **Docker**: For containerized deployment

### Step 1: WordPress Plugin Installation

1. Upload the `vortex-ai-marketplace` folder to the `/wp-content/plugins/` directory
2. Run composer dependencies:
   ```bash
   cd wp-content/plugins/vortex-ai-marketplace
   composer install --no-dev --optimize-autoloader
   ```
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings in the VORTEX AI Marketplace admin panel

### Step 2: Frontend Build Pipeline

Install and build frontend assets:

```bash
# Install Node.js dependencies
npm ci

# Lint JavaScript code
npm run lint

# Build production assets
npm run build
```

### Step 3: Python AI Server Setup

#### Option A: Docker Deployment (Recommended)

```bash
# Build Docker image
docker build -t vortex-ai-server .

# Run container
docker run -d -p 8000:8000 \
  -e AWS_ACCESS_KEY_ID=your_key \
  -e AWS_SECRET_ACCESS_KEY=your_secret \
  -e SOLANA_RPC_URL=your_solana_rpc \
  --name vortex-ai vortex-ai-server
```

#### Option B: Manual Installation

```bash
# Install Python dependencies
pip install -r requirements.txt

# Start AI server
uvicorn server.main:app --host 0.0.0.0 --port 8000
```

## Environment Variables

Configure these environment variables for production:

### AWS Configuration
```bash
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_S3_BUCKET=your_s3_bucket_name
AWS_REGION=us-east-1
```

### Solana Blockchain
```bash
SOLANA_RPC_URL=https://api.mainnet-beta.solana.com
SOLANA_PRIVATE_KEY=your_wallet_private_key
TOLA_TOKEN_MINT=your_tola_token_mint_address
```

### AI Server Configuration
```bash
AI_SERVER_URL=https://your-ai-server.com
AI_SERVER_API_KEY=your_api_key
OPENAI_API_KEY=your_openai_key
STABILITY_API_KEY=your_stability_ai_key
```

## Building the Production ZIP

To create a deployment-ready plugin ZIP:

```bash
# Run build script
npm run build

# Create production ZIP (excludes dev files)
zip -r vortex-ai-marketplace-production.zip . \
  -x "node_modules/*" "tests/*" ".git/*" "*.md" \
  "package*.json" "composer.json" "composer.lock"
```

## Configuration

### API Keys

Set up your API keys in the VORTEX settings panel:

- OpenAI API key for THORIUS and agent functionality
- Stability.ai API key for image generation
- Solana wallet configuration for TOLA token integration

### Database Setup

The plugin automatically creates all necessary database tables during activation. If you encounter any issues, use the database repair tools in the admin panel.

## Usage

### Shortcodes

```
[vortex_thorius_chat]
```
Embed the THORIUS AI chat interface.

```
[vortex_huraii_generator]
```
Embed the HURAII art generation tool.

```
[vortex_artist_dashboard]
```
Display the artist dashboard.

```
[vortex_collector_dashboard]
```
Display the collector dashboard.

### Widgets

- **THORIUS Chat Widget**: AI assistant for your website
- **HURAII Art Generator**: Create AI art directly from your sidebar
- **CLOE Recommendations**: Display personalized art recommendations
- **TOLA Balance**: Show user's TOLA token balance

## Development

### Directory Structure

```
vortex-ai-marketplace/
├── admin/                  # Admin interface files
├── includes/               # Core functionality
│   ├── agents/             # AI agent classes
│   ├── api/                # API endpoint classes
│   ├── blockchain/         # Blockchain integration
│   └── db/                 # Database models and migrations
├── public/                 # Public-facing functionality
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── partials/           # Template partials
└── languages/              # Internationalization files
```

### Adding New Agents

To add a new agent to the THORIUS orchestration system:

1. Create a new agent class in `includes/agents/`
2. Register the agent in `class-vortex-thorius-orchestrator.php`
3. Add domain-specific keywords in the `analyze_domain_distribution()` method
4. Create API endpoints for the new agent if needed

## Troubleshooting

### Common Issues

- **Database Tables Missing**: Run the database repair tool from the admin panel
- **API Connection Errors**: Verify your API keys in the settings
- **TOLA Token Integration Issues**: Check your Solana wallet configuration

### Debugging

Enable debug mode in your wp-config.php file:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('THORIUS_DEBUG', true);
```

## Roadmap

### 2025 Milestones
- MVP Launch (June)
- Artist Onboarding (July)
- Collector Launch (August)
- Miami Art Week (December)

### 2026 Goals
- NEMS Academy Launch
- Global Exhibitions
- Platform Scaling

### 2027 Vision
- Global Expansion
- Regional Hubs
- International Partnerships

## License

This project is licensed under the GPL-2.0+ License - see the LICENSE file for details.

## Credits

Developed by Marianne Nems and the VORTEX team.
