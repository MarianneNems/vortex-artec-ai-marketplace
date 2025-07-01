#!/bin/bash

# VORTEX ARTEC - Dependency Installation Script
# Installs all necessary software and dependencies for the VortexArtec workspace
echo "🚀 VORTEX ARTEC - Installing Dependencies..."
echo "==============================================="

# Set up error handling
set -euo pipefail

# Create log file
LOG_FILE="/tmp/vortex-install-$(date +%Y%m%d_%H%M%S).log"
exec 1> >(tee -a "$LOG_FILE")
exec 2> >(tee -a "$LOG_FILE" >&2)

echo "📝 Installation log: $LOG_FILE"
echo ""

# Detect operating system
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
    DISTRO=$(lsb_release -si 2>/dev/null || echo "Unknown")
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
    OS="windows"
else
    OS="unknown"
fi

echo "🖥️  Detected OS: $OS ($DISTRO)"
echo ""

# Function to install packages based on OS
install_package() {
    local package_name=$1
    local install_cmd=""
    
    case $OS in
        "linux")
            if command -v apt-get >/dev/null 2>&1; then
                install_cmd="apt-get install -y"
            elif command -v yum >/dev/null 2>&1; then
                install_cmd="yum install -y"
            elif command -v pacman >/dev/null 2>&1; then
                install_cmd="pacman -S --noconfirm"
            fi
            ;;
        "macos")
            if command -v brew >/dev/null 2>&1; then
                install_cmd="brew install"
            else
                echo "❌ Homebrew not found. Please install Homebrew first."
                return 1
            fi
            ;;
        "windows")
            if command -v choco >/dev/null 2>&1; then
                install_cmd="choco install -y"
            else
                echo "❌ Chocolatey not found. Please install Chocolatey first."
                return 1
            fi
            ;;
    esac
    
    if [[ -n "$install_cmd" ]]; then
        echo "  Installing $package_name..."
        $install_cmd $package_name || echo "  ⚠️  Failed to install $package_name"
    fi
}

# 1. Update system packages
echo "📦 Updating system packages..."
case $OS in
    "linux")
        if command -v apt-get >/dev/null 2>&1; then
            apt-get update -qq
        elif command -v yum >/dev/null 2>&1; then
            yum update -y -q
        fi
        ;;
    "macos")
        if command -v brew >/dev/null 2>&1; then
            brew update
        fi
        ;;
esac

echo "✅ System packages updated"
echo ""

# 2. Install essential development tools
echo "🔧 Installing essential development tools..."

essential_packages=(
    "git"
    "curl"
    "wget"
    "unzip"
    "zip"
    "build-essential"
    "python3"
    "python3-pip"
    "nodejs"
    "npm"
)

for package in "${essential_packages[@]}"; do
    if ! command -v ${package//-*/} >/dev/null 2>&1; then
        install_package "$package"
    else
        echo "  ✅ $package already installed"
    fi
done

echo ""

# 3. Install Python dependencies
echo "🐍 Installing Python dependencies..."

# Upgrade pip
python3 -m pip install --upgrade pip

# Core Python packages for VortexArtec
python_packages=(
    "torch"
    "torchvision"
    "torchaudio"
    "transformers"
    "diffusers"
    "accelerate"
    "opencv-python"
    "pillow"
    "numpy"
    "scipy"
    "scikit-learn"
    "pandas"
    "matplotlib"
    "seaborn"
    "requests"
    "boto3"
    "fastapi"
    "uvicorn"
    "redis"
    "cryptography"
    "pyjwt"
    "python-multipart"
    "aiofiles"
    "psutil"
    "tqdm"
)

echo "  Installing core Python packages..."
for package in "${python_packages[@]}"; do
    echo "    Installing $package..."
    python3 -m pip install "$package" --quiet || echo "    ⚠️  Failed to install $package"
done

echo "✅ Python dependencies installed"
echo ""

# 4. Install Node.js dependencies (global)
echo "📦 Installing Node.js global dependencies..."

node_packages=(
    "@aws-cli/client-s3"
    "aws-cdk"
    "typescript"
    "webpack"
    "webpack-cli"
    "sass"
    "postcss"
    "autoprefixer"
)

for package in "${node_packages[@]}"; do
    echo "  Installing $package..."
    npm install -g "$package" --silent || echo "  ⚠️  Failed to install $package"
done

echo "✅ Node.js dependencies installed"
echo ""

# 5. Install AWS CLI
echo "☁️  Installing AWS CLI..."

if ! command -v aws >/dev/null 2>&1; then
    case $OS in
        "linux")
            curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
            unzip -q awscliv2.zip
            ./aws/install
            rm -rf aws awscliv2.zip
            ;;
        "macos")
            curl "https://awscli.amazonaws.com/AWSCLIV2.pkg" -o "AWSCLIV2.pkg"
            installer -pkg AWSCLIV2.pkg -target /
            rm AWSCLIV2.pkg
            ;;
        "windows")
            install_package "awscli"
            ;;
    esac
    echo "✅ AWS CLI installed"
else
    echo "✅ AWS CLI already installed"
fi

echo ""

# 6. Install Docker (if not present)
echo "🐳 Installing Docker..."

if ! command -v docker >/dev/null 2>&1; then
    case $OS in
        "linux")
            curl -fsSL https://get.docker.com -o get-docker.sh
            sh get-docker.sh
            rm get-docker.sh
            usermod -aG docker $USER || true
            ;;
        "macos")
            echo "  Please install Docker Desktop for Mac from https://docker.com"
            ;;
        "windows")
            echo "  Please install Docker Desktop for Windows from https://docker.com"
            ;;
    esac
    echo "✅ Docker installation completed"
else
    echo "✅ Docker already installed"
fi

echo ""

# 7. Install Redis
echo "🔴 Installing Redis..."

if ! command -v redis-server >/dev/null 2>&1; then
    install_package "redis-server"
    
    # Start Redis service
    case $OS in
        "linux")
            systemctl enable redis-server || true
            systemctl start redis-server || true
            ;;
        "macos")
            brew services start redis || true
            ;;
    esac
    echo "✅ Redis installed and started"
else
    echo "✅ Redis already installed"
fi

echo ""

# 8. Create workspace directory structure
echo "📁 Creating VortexArtec workspace structure..."

WORKSPACE_DIR="$HOME/vortex-workspace"
mkdir -p "$WORKSPACE_DIR"/{src,config,logs,data,scripts,deployment}
mkdir -p "$WORKSPACE_DIR"/data/{models,datasets,cache,output}
mkdir -p "$WORKSPACE_DIR"/config/{aws,runpod,wordpress}
mkdir -p "$WORKSPACE_DIR"/scripts/{deployment,maintenance,backup}

echo "✅ Workspace structure created at: $WORKSPACE_DIR"
echo ""

# 9. Create configuration templates
echo "⚙️  Creating configuration templates..."

# AWS configuration template
cat > "$WORKSPACE_DIR/config/aws/aws-config-template.json" << 'EOF'
{
    "profile": "vortexartec",
    "region": "us-east-1",
    "s3_buckets": {
        "public_art": "vortex-user-generated-art",
        "user_galleries": "vortex-user-galleries",
        "marketplace": "vortex-marketplace-assets"
    },
    "lambda_functions": {
        "api_bridge": "VortexArtecAPIBridge"
    }
}
EOF

# RunPod configuration template
cat > "$WORKSPACE_DIR/config/runpod/runpod-config-template.json" << 'EOF'
{
    "private_vault": {
        "location": "/workspace/vortex_private_vault",
        "algorithms_dir": "proprietary_algorithms",
        "memory_dir": "deep_learning_memory",
        "api_port": 8889
    },
    "security": {
        "encryption_enabled": true,
        "access_control": "admin_only",
        "audit_logging": true
    }
}
EOF

# WordPress integration template
cat > "$WORKSPACE_DIR/config/wordpress/wp-config-additions.php" << 'EOF'
<?php
// VORTEX ARTEC - WordPress Configuration Additions
// Add these lines to your wp-config.php file

// AWS Configuration
define('VORTEX_AWS_REGION', 'us-east-1');
define('VORTEX_S3_BUCKET_PUBLIC_ART', 'vortex-user-generated-art');
define('VORTEX_S3_BUCKET_USER_GALLERIES', 'vortex-user-galleries');
define('VORTEX_S3_BUCKET_MARKETPLACE', 'vortex-marketplace-assets');

// RunPod Private Vault (Internal Use Only)
define('VORTEX_RUNPOD_PRIVATE_ENDPOINT', 'http://your-runpod-ip:8889');
define('VORTEX_VAULT_ACCESS_TOKEN', 'your-secure-token-here');

// Security Settings
define('VORTEX_ENCRYPTION_KEY', 'your-encryption-key-here');
define('VORTEX_API_SECRET', 'your-api-secret-here');

// Debug Settings (disable in production)
define('VORTEX_DEBUG', false);
define('VORTEX_LOG_LEVEL', 'INFO');
?>
EOF

echo "✅ Configuration templates created"
echo ""

# 10. Create utility scripts
echo "🛠️  Creating utility scripts..."

# AWS setup script
cat > "$WORKSPACE_DIR/scripts/deployment/setup-aws.sh" << 'EOF'
#!/bin/bash
# Quick AWS setup script for VortexArtec
echo "Setting up AWS for VortexArtec..."

# Check if AWS CLI is configured
if ! aws sts get-caller-identity >/dev/null 2>&1; then
    echo "Please configure AWS CLI first:"
    echo "  aws configure --profile vortexartec"
    exit 1
fi

# Run the main AWS setup
./aws-vortex-cloud-setup.sh
EOF

# RunPod vault setup script
cat > "$WORKSPACE_DIR/scripts/deployment/setup-runpod-vault.sh" << 'EOF'
#!/bin/bash
# Quick RunPod private vault setup script
echo "Setting up RunPod private vault..."

# Create vault directory structure
mkdir -p /workspace/vortex_private_vault/{proprietary_algorithms,deep_learning_memory,secure_api_bridge,logs}
chmod -R 700 /workspace/vortex_private_vault

echo "Private vault structure created"
echo "Run the full setup with: ./vortex-private-vault-setup.sh"
EOF

# WordPress integration script
cat > "$WORKSPACE_DIR/scripts/deployment/integrate-wordpress.sh" << 'EOF'
#!/bin/bash
# WordPress integration script for VortexArtec
echo "Integrating VortexArtec with WordPress..."

WP_PATH="${1:-/var/www/html}"
PLUGIN_PATH="$WP_PATH/wp-content/plugins/vortex-ai-marketplace"

if [ ! -d "$WP_PATH" ]; then
    echo "WordPress not found at: $WP_PATH"
    exit 1
fi

echo "WordPress integration will be configured at: $PLUGIN_PATH"
EOF

# Make scripts executable
chmod +x "$WORKSPACE_DIR"/scripts/deployment/*.sh

echo "✅ Utility scripts created"
echo ""

# 11. Create environment setup script
cat > "$WORKSPACE_DIR/setup-environment.sh" << 'EOF'
#!/bin/bash
# VortexArtec Environment Setup
echo "🚀 Setting up VortexArtec environment..."

# Source Python virtual environment
if [ -d "venv" ]; then
    source venv/bin/activate
    echo "✅ Python virtual environment activated"
fi

# Set environment variables
export VORTEX_WORKSPACE="$PWD"
export VORTEX_CONFIG_DIR="$PWD/config"
export VORTEX_DATA_DIR="$PWD/data"
export VORTEX_LOG_DIR="$PWD/logs"

# Create necessary directories
mkdir -p logs data/models data/cache

echo "✅ VortexArtec environment ready"
echo "   Workspace: $VORTEX_WORKSPACE"
echo "   Config: $VORTEX_CONFIG_DIR"
echo "   Data: $VORTEX_DATA_DIR"
echo "   Logs: $VORTEX_LOG_DIR"
EOF

chmod +x "$WORKSPACE_DIR/setup-environment.sh"

# 12. Final verification
echo "🔍 Verifying installation..."

# Check critical dependencies
dependencies_check=(
    "python3:Python 3"
    "pip:Python Package Manager"
    "node:Node.js"
    "npm:Node Package Manager"
    "aws:AWS CLI"
    "git:Git Version Control"
    "docker:Docker"
    "redis-server:Redis Server"
)

all_good=true
for dep_check in "${dependencies_check[@]}"; do
    IFS=':' read -r cmd desc <<< "$dep_check"
    if command -v "$cmd" >/dev/null 2>&1; then
        version=$(${cmd} --version 2>/dev/null | head -1 || echo "installed")
        echo "  ✅ $desc: $version"
    else
        echo "  ❌ $desc: Not found"
        all_good=false
    fi
done

echo ""

if $all_good; then
    echo "🎉 INSTALLATION COMPLETE!"
    echo "======================================="
    echo ""
    echo "✅ All dependencies installed successfully"
    echo "✅ Workspace created at: $WORKSPACE_DIR"
    echo "✅ Configuration templates ready"
    echo "✅ Utility scripts available"
    echo ""
    echo "🔄 NEXT STEPS:"
    echo "   1. Configure AWS credentials:"
    echo "      aws configure --profile vortexartec"
    echo ""
    echo "   2. Set up AWS infrastructure:"
    echo "      cd $WORKSPACE_DIR"
    echo "      ./aws-vortex-cloud-setup.sh"
    echo ""
    echo "   3. Configure RunPod private vault:"
    echo "      ./vortex-private-vault-setup.sh"
    echo ""
    echo "   4. Integrate with WordPress:"
    echo "      ./scripts/deployment/integrate-wordpress.sh"
    echo ""
    echo "📝 Installation log saved to: $LOG_FILE"
    echo ""
    echo "🎨 Your VortexArtec workspace is ready for visual generation!"
else
    echo "⚠️  INSTALLATION INCOMPLETE"
    echo "Some dependencies failed to install. Check the log for details."
    echo "Log file: $LOG_FILE"
fi 