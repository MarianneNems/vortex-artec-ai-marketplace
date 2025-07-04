#!/bin/bash

# VORTEX Parameter Encryption Script
# Encrypts sensitive parameters using AWS KMS

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
KMS_KEY_ALIAS="alias/vortex-secret"
PARAMS_FILE="${SCRIPT_DIR}/params.json"
ENCRYPTED_FILE="${SCRIPT_DIR}/encrypted-params.bin"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
log() {
    echo -e "${GREEN}[ENCRYPT]${NC} $1"
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

# Check if params.json exists
if [ ! -f "$PARAMS_FILE" ]; then
    error "Parameters file not found: $PARAMS_FILE"
fi

# Validate JSON format
if ! jq empty "$PARAMS_FILE" 2>/dev/null; then
    error "Invalid JSON format in $PARAMS_FILE"
fi

# Check AWS credentials
if ! aws sts get-caller-identity &> /dev/null; then
    error "AWS credentials not configured. Run 'aws configure' first."
fi

# Check if KMS key exists
if ! aws kms describe-key --key-id "$KMS_KEY_ALIAS" &> /dev/null; then
    warn "KMS key $KMS_KEY_ALIAS not found. Creating it..."
    
    # Create KMS key
    KEY_ID=$(aws kms create-key --description "VORTEX Secret Parameters Encryption Key" --query 'KeyMetadata.KeyId' --output text)
    
    # Create alias
    aws kms create-alias --alias-name "$KMS_KEY_ALIAS" --target-key-id "$KEY_ID"
    
    log "Created KMS key: $KEY_ID with alias: $KMS_KEY_ALIAS"
fi

# Display parameters to be encrypted (without values)
log "Parameters to be encrypted:"
jq -r 'keys | .[]' "$PARAMS_FILE" | while read -r key; do
    echo "  - $key"
done

# Confirm encryption
read -p "Do you want to encrypt these parameters? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log "Encryption cancelled."
    exit 0
fi

# Encrypt the parameters
log "Encrypting parameters..."
aws kms encrypt \
    --key-id "$KMS_KEY_ALIAS" \
    --plaintext "fileb://$PARAMS_FILE" \
    --output text \
    --query CiphertextBlob > "$ENCRYPTED_FILE"

if [ $? -eq 0 ]; then
    log "Parameters encrypted successfully: $ENCRYPTED_FILE"
    
    # Set appropriate permissions
    chmod 600 "$ENCRYPTED_FILE"
    
    # Optionally remove the plaintext file
    read -p "Do you want to remove the plaintext parameters file? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm "$PARAMS_FILE"
        log "Plaintext parameters file removed."
    fi
    
    # Display file sizes
    log "Encrypted file size: $(stat -c%s "$ENCRYPTED_FILE" 2>/dev/null || stat -f%z "$ENCRYPTED_FILE") bytes"
    
else
    error "Encryption failed!"
fi

log "Encryption completed successfully!"
log "To decrypt, run: ./decrypt-params.sh" 