#!/bin/bash
#
# VORTEX AI Marketplace - Environment Setup Script
# 
# This script sets up environment variables for production deployment
# Usage: source deploy/setup-env.sh
#

echo "🔧 Setting up VORTEX AI Marketplace environment variables..."

# AWS S3 Configuration
export AWS_ACCESS_KEY_ID="AKIAEXAMPLEKEYID12345"
export AWS_SECRET_ACCESS_KEY="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
export AWS_DEFAULT_REGION="us-east-1"
export AWS_S3_BUCKET="vortexartec.com-client-art"

# RunPod Configuration
export RUNPOD_API_KEY="your-runpod-api-key-here"
export RUNPOD_VAULT_ID="your-vault-id-here"

# AI Services
export OPENAI_API_KEY="sk-your-openai-api-key-here"
export STABILITY_API_KEY="sk-your-stability-ai-key-here"

# Solana Blockchain
export SOLANA_RPC_URL="https://api.mainnet-beta.solana.com"
export SOLANA_PRIVATE_KEY="your-solana-wallet-private-key-here"
export TOLA_TOKEN_MINT="your-tola-token-mint-address-here"

# WordPress Configuration
export WP_DEBUG="false"
export WP_DEBUG_LOG="false"
export VORTEX_DEBUG="false"

# Security Configuration
export VORTEX_ENCRYPTION_KEY="your-32-character-encryption-key-here"
export VORTEX_API_SECRET="your-api-secret-key-here"

# Performance Configuration
export VORTEX_CACHE_ENABLED="true"
export VORTEX_REDIS_HOST="127.0.0.1"
export VORTEX_REDIS_PORT="6379"

# Verify environment variables are set
echo "✅ Environment variables configured:"
echo "   AWS_S3_BUCKET: $AWS_S3_BUCKET"
echo "   RUNPOD_VAULT_ID: $RUNPOD_VAULT_ID"
echo "   SOLANA_RPC_URL: $SOLANA_RPC_URL"
echo "   VORTEX_CACHE_ENABLED: $VORTEX_CACHE_ENABLED"

echo ""
echo "⚠️  IMPORTANT SECURITY NOTICE:"
echo "   1. Replace all placeholder keys with actual production values"
echo "   2. Store this file securely outside web root"
echo "   3. Set restrictive file permissions: chmod 600 setup-env.sh"
echo "   4. Never commit real keys to version control"
echo ""
echo "🚀 Environment setup complete!"
echo "   Run: source deploy/setup-env.sh" 