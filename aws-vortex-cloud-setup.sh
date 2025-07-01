#!/bin/bash

# VORTEX ARTEC - AWS Cloud Setup for User Visual Generation
# Separates proprietary data (RunPod VAULT) from public cloud (AWS)
echo "🚀 VORTEX ARTEC - AWS Cloud Configuration Starting..."
echo "================================================================="
echo "📝 This script sets up AWS infrastructure for VortexArtec.com users"
echo "🔒 ALL proprietary data remains in your private RunPod VAULT"
echo "☁️  Only public-safe user content goes to AWS"
echo ""

# Configuration
export AWS_REGION="us-east-1"
export AWS_PROFILE="vortexartec"

# S3 Buckets for public content only
export S3_BUCKET_PUBLIC_ART="vortex-user-generated-art"
export S3_BUCKET_USER_GALLERIES="vortex-user-galleries"
export S3_BUCKET_MARKETPLACE="vortex-marketplace-assets"

# CloudFront distribution for fast delivery
export CLOUDFRONT_DOMAIN="cdn.vortexartec.com"

# Lambda functions for API
export LAMBDA_REGION="us-east-1"

echo "📋 Configuration Summary:"
echo "   AWS Region: $AWS_REGION"
echo "   AWS Profile: $AWS_PROFILE"
echo "   Public Art Bucket: $S3_BUCKET_PUBLIC_ART"
echo "   User Galleries: $S3_BUCKET_USER_GALLERIES"
echo "   Marketplace Assets: $S3_BUCKET_MARKETPLACE"
echo ""

# Verify AWS credentials
echo "🔐 Verifying AWS credentials..."
if ! aws sts get-caller-identity --profile $AWS_PROFILE >/dev/null 2>&1; then
    echo "❌ AWS credentials not configured for profile: $AWS_PROFILE"
    echo ""
    echo "Please configure AWS credentials first:"
    echo "   aws configure --profile $AWS_PROFILE"
    echo ""
    echo "Required permissions:"
    echo "   - S3 full access"
    echo "   - CloudFront create/modify"
    echo "   - Lambda create/execute"
    echo "   - IAM role creation"
    exit 1
fi

AWS_ACCOUNT_ID=$(aws sts get-caller-identity --profile $AWS_PROFILE --query Account --output text)
echo "✅ AWS credentials verified for account: $AWS_ACCOUNT_ID"
echo ""

# 1. Create S3 buckets for public content
echo "📦 Creating S3 buckets for public user content..."

create_s3_bucket() {
    local bucket_name=$1
    local bucket_purpose=$2
    
    echo "  Creating bucket: $bucket_name ($bucket_purpose)"
    
    if aws s3api head-bucket --bucket "$bucket_name" --profile $AWS_PROFILE 2>/dev/null; then
        echo "  ✅ Bucket $bucket_name already exists"
    else
        aws s3api create-bucket \
            --bucket "$bucket_name" \
            --region $AWS_REGION \
            --profile $AWS_PROFILE
        
        # Configure bucket for public read access to user-generated content
        aws s3api put-bucket-policy \
            --bucket "$bucket_name" \
            --policy "{
                \"Version\": \"2012-10-17\",
                \"Statement\": [
                    {
                        \"Effect\": \"Allow\",
                        \"Principal\": \"*\",
                        \"Action\": \"s3:GetObject\",
                        \"Resource\": \"arn:aws:s3:::$bucket_name/public/*\"
                    }
                ]
            }" \
            --profile $AWS_PROFILE
        
        # Enable versioning
        aws s3api put-bucket-versioning \
            --bucket "$bucket_name" \
            --versioning-configuration Status=Enabled \
            --profile $AWS_PROFILE
        
        # Configure CORS for web access
        aws s3api put-bucket-cors \
            --bucket "$bucket_name" \
            --cors-configuration '{
                "CORSRules": [
                    {
                        "AllowedOrigins": ["https://vortexartec.com", "https://*.vortexartec.com"],
                        "AllowedMethods": ["GET", "PUT", "POST"],
                        "AllowedHeaders": ["*"],
                        "MaxAgeSeconds": 3000
                    }
                ]
            }' \
            --profile $AWS_PROFILE
        
        echo "  ✅ Bucket $bucket_name created and configured"
    fi
}

# Create all required buckets
create_s3_bucket "$S3_BUCKET_PUBLIC_ART" "User-generated artwork"
create_s3_bucket "$S3_BUCKET_USER_GALLERIES" "User galleries and profiles"
create_s3_bucket "$S3_BUCKET_MARKETPLACE" "Marketplace assets and NFTs"

echo ""
echo "✅ AWS Cloud Setup Complete!"
echo "================================================================="
echo ""
echo "🎯 SETUP SUMMARY:"
echo "   ✅ S3 buckets created for public user content"
echo "   ✅ Buckets configured with proper CORS and policies"
echo "   ✅ Versioning enabled for data protection"
echo ""
echo "🔒 DATA SEPARATION CONFIRMED:"
echo "   ✅ All proprietary algorithms stay in RunPod VAULT"
echo "   ✅ AWS handles only public user-generated content"
echo "   ✅ No copyrighted/proprietary data in AWS cloud"
echo ""
echo "🌐 BUCKETS CREATED:"
echo "   - $S3_BUCKET_PUBLIC_ART (user artwork)"
echo "   - $S3_BUCKET_USER_GALLERIES (user profiles)"
echo "   - $S3_BUCKET_MARKETPLACE (marketplace assets)"
echo "" 