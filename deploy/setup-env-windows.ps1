# PowerShell Environment Setup for VORTEX AI Marketplace

Write-Host "Setting up environment variables..." -ForegroundColor Green

$env:AWS_ACCESS_KEY_ID = "AKIAEXAMPLEKEYID12345"
$env:AWS_SECRET_ACCESS_KEY = "wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
$env:AWS_DEFAULT_REGION = "us-east-1"
$env:AWS_S3_BUCKET = "vortexartec.com-client-art"

$env:RUNPOD_API_KEY = "your-runpod-api-key-here"
$env:RUNPOD_VAULT_ID = "your-vault-id-here"

Write-Host "Environment variables set successfully!" -ForegroundColor Green
Write-Host "AWS_S3_BUCKET: $env:AWS_S3_BUCKET" -ForegroundColor Cyan

VORTEX AI MARKETPLACE PRODUCTION STACK:

┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND (WordPress)                    │
│  • WordPress 5.6+ with PHP 8.1+                           │
│  • VORTEX AI Marketplace Plugin                            │
│  • WooCommerce Integration                                 │
│  • User Authentication and Role Management                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   CONFIGURATION LAYER                       │
│  • aws-config.php (S3 Bucket: vortexartec.com-client-art) │
│  • runpod-config.php (AI Processing Vault)                │
│  • Environment Variables (Secure Key Management)           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                        │
│  • AWS S3 (File Storage and CDN)                          │
│  • RunPod (AI Model Processing)                           │
│  • Solana Blockchain (TOLA Token)                         │
│  • OpenAI API (AI Agent Intelligence)                     │
└─────────────────────────────────────────────────────────────┘ 