# VortexArtec AWS Cloud Deployment Guide

## 🎯 Overview

This guide walks you through setting up AWS cloud infrastructure for **VortexArtec.com users** to generate visuals, while ensuring **all proprietary algorithms and data remain secure in your RunPod VAULT**.

## 🔒 Data Separation Architecture

### PRIVATE (RunPod VAULT):
- ✅ **Seed Art Technique algorithms** - Your proprietary IP
- ✅ **Zodiac Analysis systems** - Copyrighted analysis methods  
- ✅ **Deep learning memory** - Continuous learning data
- ✅ **Model weights** - Trained AI models
- ✅ **Sacred geometry algorithms** - Your secret sauce
- ✅ **Artist style fingerprints** - Private analysis data

### PUBLIC (AWS Cloud):
- 📤 **User-generated artwork** - With user consent
- 📤 **Public-safe analysis results** - Generic scores only
- 📤 **Marketplace assets** - NFT metadata
- 📤 **User profiles** - Public information only

## 🚀 Quick Start

### Step 1: Install Dependencies
```bash
# Run the dependency installer
sudo bash install-vortex-dependencies.sh
```

### Step 2: Configure AWS Credentials
```bash
# Set up your AWS profile
aws configure --profile vortexartec
# Enter your AWS Access Key ID
# Enter your AWS Secret Access Key  
# Region: us-east-1
# Output format: json
```

### Step 3: Deploy AWS Infrastructure
```bash
# Run the AWS cloud setup
bash aws-vortex-cloud-setup.sh
```

### Step 4: Set Up RunPod Private Vault
```bash
# On your RunPod instance, run:
bash vortex-private-vault-setup.sh
```

## 📋 Detailed Setup Process

### Prerequisites

1. **AWS Account** with appropriate permissions:
   - S3 full access
   - Lambda create/execute
   - API Gateway management
   - CloudFront distribution
   - IAM role creation

2. **RunPod Instance** (existing):
   - Your current A40 instance
   - SSH/terminal access
   - Sufficient storage for algorithms

3. **Domain Setup**:
   - VortexArtec.com DNS access
   - SSL certificate capability

### Phase 1: Dependencies Installation

The `install-vortex-dependencies.sh` script installs:

**System Dependencies:**
- Git, curl, wget, unzip
- Python 3.x with pip
- Node.js with npm
- Docker and Docker Compose
- Redis server
- AWS CLI v2

**Python Packages:**
- PyTorch with CUDA support
- Transformers and Diffusers
- OpenCV and Pillow for image processing
- NumPy, SciPy, scikit-learn
- FastAPI and Uvicorn for APIs
- Boto3 for AWS integration
- Cryptography for secure operations

**Node.js Packages:**
- AWS SDK components
- TypeScript and build tools
- Sass and PostCSS
- Webpack for bundling

### Phase 2: AWS Infrastructure Setup

The `aws-vortex-cloud-setup.sh` script creates:

**S3 Buckets:**
- `vortex-user-generated-art` - User uploads and public artwork
- `vortex-user-galleries` - User profiles and gallery data  
- `vortex-marketplace-assets` - NFT metadata and marketplace data

**Security Configuration:**
- Public read access for `/public/*` paths only
- CORS configuration for VortexArtec.com domain
- Versioning enabled for data protection
- Bucket policies for secure access

**API Infrastructure:**
- Lambda function for secure API operations
- API Gateway for public endpoints
- IAM roles with minimal necessary permissions
- CloudFront CDN for fast global delivery

### Phase 3: RunPod Private Vault

Your existing RunPod becomes the secure vault:

**Directory Structure:**
```
/workspace/vortex_private_vault/
├── proprietary_algorithms/     # Your secret algorithms
├── deep_learning_memory/       # Continuous learning data
├── secure_api_bridge/          # Safe data bridge to AWS
└── logs/                       # Private operation logs
```

**Security Features:**
- All algorithms encrypted at rest
- No external network access to algorithms
- Audit logging for all operations
- Access control for admin only

### Phase 4: Integration Bridge

The secure bridge between RunPod and AWS:

**Data Flow:**
1. User uploads artwork → VortexArtec.com
2. WordPress sends to RunPod private API
3. RunPod processes with proprietary algorithms
4. Safe, sanitized results sent to AWS S3
5. User receives public-safe analysis results

**Security Guarantees:**
- No proprietary data ever leaves RunPod
- All learning data stays private
- Only generic scores/categories go public
- Full audit trail maintained

## 🔧 Configuration Files

After setup, you'll have these configuration files:

### WordPress Integration (`wp-config.php` additions):
```php
// AWS Configuration
define('VORTEX_AWS_REGION', 'us-east-1');
define('VORTEX_S3_BUCKET_PUBLIC_ART', 'vortex-user-generated-art');
define('VORTEX_S3_BUCKET_USER_GALLERIES', 'vortex-user-galleries');
define('VORTEX_S3_BUCKET_MARKETPLACE', 'vortex-marketplace-assets');

// RunPod Private Vault (Internal Use Only)
define('VORTEX_RUNPOD_PRIVATE_ENDPOINT', 'http://your-runpod-ip:8889');
define('VORTEX_VAULT_ACCESS_TOKEN', 'your-secure-token');

// Security Settings
define('VORTEX_ENCRYPTION_KEY', 'your-encryption-key');
define('VORTEX_API_SECRET', 'your-api-secret');
```

### AWS Configuration (`vortex-aws-config.json`):
```json
{
    "aws_region": "us-east-1",
    "aws_profile": "vortexartec",
    "s3_buckets": {
        "public_art": "vortex-user-generated-art",
        "user_galleries": "vortex-user-galleries",
        "marketplace": "vortex-marketplace-assets"
    },
    "api_gateway": {
        "endpoint": "https://your-api-id.execute-api.us-east-1.amazonaws.com/prod"
    }
}
```

## 🧪 Testing Your Setup

### Test 1: AWS S3 Access
```bash
# Test S3 bucket access
aws s3 ls s3://vortex-user-generated-art --profile vortexartec
```

### Test 2: RunPod Private Vault
```bash
# Test private vault API (on RunPod)
curl http://127.0.0.1:8889/api/v1/health
```

### Test 3: End-to-End Integration
```bash
# Test full workflow
curl -X POST "https://your-api-gateway-url/prod/art" \
  -H "Content-Type: application/json" \
  -d '{"test": "artwork upload"}'
```

## 🔄 Operational Workflows

### Daily Operations:
1. **Monitor RunPod vault** - Check private algorithm status
2. **Review AWS costs** - Monitor S3 storage and API usage
3. **Check integration bridge** - Ensure data flow is working
4. **Backup private data** - Secure your proprietary algorithms

### User Visual Generation Flow:
1. User visits VortexArtec.com
2. Uploads artwork through WordPress interface
3. WordPress sends to RunPod private API
4. RunPod processes with proprietary algorithms
5. Safe results stored in AWS S3
6. User receives analysis via fast CDN delivery

### Security Monitoring:
- All private algorithm access logged
- AWS API calls monitored
- Data separation verified daily
- No proprietary data in AWS confirmed

## 💰 Cost Optimization

### AWS Costs (Estimated):
- **S3 Storage**: ~$0.023/GB/month
- **Lambda Functions**: ~$0.20/1M requests
- **API Gateway**: ~$3.50/1M requests
- **CloudFront CDN**: ~$0.085/GB transfer
- **Total**: ~$50-200/month depending on usage

### RunPod Costs (Existing):
- **A40 Instance**: ~$0.40/hour (~$300/month)
- **Private vault processing**: Included
- **No additional charges**: For proprietary data

## 🆘 Troubleshooting

### Common Issues:

**AWS Credentials Error:**
```bash
# Reconfigure AWS CLI
aws configure --profile vortexartec
```

**S3 Access Denied:**
```bash
# Check bucket policies
aws s3api get-bucket-policy --bucket vortex-user-generated-art --profile vortexartec
```

**RunPod Vault Connection Failed:**
```bash
# Check vault status
curl http://127.0.0.1:8889/api/v1/private-status
```

**WordPress Integration Issues:**
```bash
# Check WordPress error log
tail -f /var/log/nginx/error.log
```

## 🔐 Security Best Practices

1. **Never expose RunPod IP publicly** - Use VPN or SSH tunneling
2. **Rotate access tokens regularly** - Update vault access tokens monthly
3. **Monitor private algorithm access** - Review audit logs daily
4. **Backup proprietary data** - Encrypt and store securely
5. **Separate environments** - Keep dev/test separate from production

## 🎯 Success Metrics

After successful deployment, you should see:

✅ **AWS Infrastructure**: S3 buckets, Lambda, API Gateway all operational  
✅ **RunPod Vault**: Private algorithms secure and processing requests  
✅ **Data Separation**: No proprietary data in AWS, confirmed via auditing  
✅ **User Experience**: Fast visual generation via CDN delivery  
✅ **Security**: All access properly authenticated and logged  
✅ **Performance**: <2 second response times for user requests  

## 🚀 Next Steps

1. **Scale Up**: Add more S3 buckets for different content types
2. **Global CDN**: Expand CloudFront to more regions
3. **Enhanced Security**: Add WAF protection to API Gateway
4. **Monitoring**: Set up CloudWatch alerts for usage spikes
5. **Backup Strategy**: Implement automated backups for private vault

## 📞 Support

If you encounter issues:

1. **Check the installation logs** - Located in `/tmp/vortex-install-*.log`
2. **Verify AWS permissions** - Ensure your IAM user has required access
3. **Test RunPod connectivity** - Confirm your vault is accessible via SSH
4. **Review configuration files** - Check all paths and tokens are correct

---

**🎨 Your VortexArtec.com users can now safely generate visuals with your proprietary algorithms staying completely private in your RunPod VAULT!** 