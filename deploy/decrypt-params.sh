#!/bin/bash

# VORTEX Parameter Decryption Script
# Decrypts sensitive parameters using AWS KMS

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENCRYPTED_FILE="${SCRIPT_DIR}/encrypted-params.bin"
PARAMS_FILE="${SCRIPT_DIR}/params.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
log() {
    echo -e "${GREEN}[DECRYPT]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    error "AWS CLI is not installed. Please install it first."
fi

# Check if encrypted file exists
if [ ! -f "$ENCRYPTED_FILE" ]; then
    error "Encrypted parameters file not found: $ENCRYPTED_FILE"
fi

# Check AWS credentials
if ! aws sts get-caller-identity &> /dev/null; then
    error "AWS credentials not configured. Run 'aws configure' first."
fi

# Check if output file already exists
if [ -f "$PARAMS_FILE" ]; then
    warn "Decrypted parameters file already exists: $PARAMS_FILE"
    read -p "Do you want to overwrite it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Decryption cancelled."
        exit 0
    fi
fi

# Decrypt the parameters
log "Decrypting parameters..."
aws kms decrypt \
    --ciphertext-blob "fileb://$ENCRYPTED_FILE" \
    --output text \
    --query Plaintext | base64 --decode > "$PARAMS_FILE"

if [ $? -eq 0 ]; then
    log "Parameters decrypted successfully: $PARAMS_FILE"
    
    # Set appropriate permissions
    chmod 600 "$PARAMS_FILE"
    
    # Validate JSON format
    if ! jq empty "$PARAMS_FILE" 2>/dev/null; then
        error "Decrypted content is not valid JSON!"
    fi
    
    # Display parameter keys (without values)
    log "Decrypted parameters:"
    jq -r 'keys | .[]' "$PARAMS_FILE" | while read -r key; do
        echo "  - $key"
    done
    
    # Display file sizes
    log "Decrypted file size: $(stat -c%s "$PARAMS_FILE" 2>/dev/null || stat -f%z "$PARAMS_FILE") bytes"
    
    # Security reminder
    warn "SECURITY REMINDER:"
    warn "- The decrypted file contains sensitive data"
    warn "- Do not commit this file to version control"
    warn "- Remove this file after use if not needed"
    warn "- File permissions set to 600 (owner read/write only)"
    
else
    error "Decryption failed!"
fi

log "Decryption completed successfully!" 