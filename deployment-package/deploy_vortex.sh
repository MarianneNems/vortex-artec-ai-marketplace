#!/usr/bin/env bash
set -euo pipefail

echo "🚀 VortexArtec AI Server Deployment Starting..."
echo "================================================"

### 1. Variables to adjust ###
GPU_VOLUME_DEVICE="/dev/nvme1n1"             # or the path your provider uses
MOUNT_POINT="/mnt/vortex"
AWS_PROFILE="vortexai"
AWS_REGION="us-east-2"
S3_BUCKET="vortexartec.com-client-art"
AUTOMATIC1111_DIR="/opt/stable-diffusion-webui"
AI_ENV_DIR="/opt/ai-env"

# AWS credentials (export these as environment variables for security)
AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-YOUR_AWS_ACCESS_KEY_ID}"
AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-YOUR_AWS_SECRET_ACCESS_KEY}"

# Model URLs (update these with your actual model locations)
SDXL_MODEL_URL="https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0/resolve/main/sd_xl_base_1.0.safetensors"
SD15_MODEL_URL="https://huggingface.co/runwayml/stable-diffusion-v1-5/resolve/main/v1-5-pruned-emaonly.safetensors"

### 2. System update & prerequisites ###
echo "📦 Updating system and installing prerequisites..."
export DEBIAN_FRONTEND=noninteractive
apt update && apt upgrade -y

# Install basic packages
apt install -y git wget curl unzip build-essential software-properties-common
apt install -y python3.10 python3.10-venv python3.10-dev python3-pip
apt install -y htop tree screen tmux nano vim

# Install NVIDIA drivers and CUDA toolkit
echo "🎮 Installing NVIDIA drivers and CUDA toolkit..."
apt install -y ubuntu-drivers-common
ubuntu-drivers autoinstall

# Add NVIDIA package repositories
wget https://developer.download.nvidia.com/compute/cuda/repos/ubuntu2004/x86_64/cuda-keyring_1.0-1_all.deb
dpkg -i cuda-keyring_1.0-1_all.deb
apt update

# Install CUDA 11.7 (matches PyTorch requirements)
apt install -y cuda-11-7 cuda-toolkit-11-7

# Install AWS CLI
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
./aws/install
rm -rf aws awscliv2.zip

### 3. Configure UFW Firewall ###
echo "🔒 Configuring firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 7860/tcp  # Stable Diffusion WebUI
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw --force enable

### 4. Mount the network volume ###
echo "💾 Setting up network volume..."
# Create mount point
mkdir -p $MOUNT_POINT

# Check if device exists before mounting
if [ -b "$GPU_VOLUME_DEVICE" ]; then
    # Format if not already formatted (be careful with this!)
    if ! blkid "$GPU_VOLUME_DEVICE"; then
        echo "⚠️  Formatting new volume..."
        mkfs.ext4 "$GPU_VOLUME_DEVICE"
    fi
    
    # Add to fstab if not already present
    if ! grep -q "$GPU_VOLUME_DEVICE" /etc/fstab; then
        echo "${GPU_VOLUME_DEVICE}    ${MOUNT_POINT}    ext4    defaults,nofail    0    2" >> /etc/fstab
    fi
    
    mount -a
    
    # Set permissions
    chown -R root:root $MOUNT_POINT
    chmod 755 $MOUNT_POINT
else
    echo "⚠️  Warning: GPU volume device $GPU_VOLUME_DEVICE not found. Continuing without network volume..."
fi

### 5. Create Python virtual environment ###
echo "🐍 Setting up Python environment..."
python3.10 -m venv $AI_ENV_DIR
source $AI_ENV_DIR/bin/activate

# Upgrade pip and install base packages
pip install --upgrade pip setuptools wheel

# Install PyTorch with CUDA 11.7 support
pip install torch torchvision torchaudio --extra-index-url https://download.pytorch.org/whl/cu117

# Install additional AI/ML packages
pip install transformers accelerate diffusers xformers opencv-python pillow numpy

### 6. Configure AWS CLI ###
echo "☁️  Configuring AWS CLI..."
if [ "$AWS_ACCESS_KEY_ID" != "YOUR_AWS_ACCESS_KEY_ID" ] && [ "$AWS_SECRET_ACCESS_KEY" != "YOUR_AWS_SECRET_ACCESS_KEY" ]; then
    aws configure set aws_access_key_id     "$AWS_ACCESS_KEY_ID"     --profile $AWS_PROFILE
    aws configure set aws_secret_access_key "$AWS_SECRET_ACCESS_KEY" --profile $AWS_PROFILE
    aws configure set region                "$AWS_REGION"            --profile $AWS_PROFILE
    aws configure set output                "json"                   --profile $AWS_PROFILE
    echo "✅ AWS CLI configured with profile: $AWS_PROFILE"
else
    echo "⚠️  AWS credentials not provided. Please configure manually:"
    echo "   aws configure --profile $AWS_PROFILE"
fi

### 7. Clone and setup AUTOMATIC1111 ###
echo "🎨 Setting up Stable Diffusion WebUI..."
if [ ! -d "$AUTOMATIC1111_DIR" ]; then
    git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui.git $AUTOMATIC1111_DIR
fi

cd $AUTOMATIC1111_DIR

# Create model directories
mkdir -p models/Stable-diffusion models/Lora models/VAE models/ESRGAN
mkdir -p extensions/sd-webui-controlnet/models

# Install ControlNet extension
if [ ! -d "extensions/sd-webui-controlnet" ]; then
    git clone https://github.com/Mikubill/sd-webui-controlnet.git extensions/sd-webui-controlnet
fi

# Install additional useful extensions
if [ ! -d "extensions/sd-webui-additional-networks" ]; then
    git clone https://github.com/kohya-ss/sd-webui-additional-networks.git extensions/sd-webui-additional-networks
fi

### 8. Download models ###
echo "📥 Downloading AI models..."
cd models/Stable-diffusion

# Download SDXL base model (if not exists)
if [ ! -f "sd_xl_base_1.0.safetensors" ]; then
    echo "Downloading SDXL base model..."
    wget -O sd_xl_base_1.0.safetensors "$SDXL_MODEL_URL" || echo "⚠️  SDXL download failed"
fi

# Download SD 1.5 model (if not exists)
if [ ! -f "v1-5-pruned-emaonly.safetensors" ]; then
    echo "Downloading SD 1.5 model..."
    wget -O v1-5-pruned-emaonly.safetensors "$SD15_MODEL_URL" || echo "⚠️  SD 1.5 download failed"
fi

# Download ControlNet models
cd ../extensions/sd-webui-controlnet/models
CONTROLNET_MODELS=(
    "https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_canny.pth"
    "https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_openpose.pth"
    "https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_depth.pth"
)

for model_url in "${CONTROLNET_MODELS[@]}"; do
    model_name=$(basename "$model_url")
    if [ ! -f "$model_name" ]; then
        echo "Downloading ControlNet model: $model_name"
        wget "$model_url" || echo "⚠️  Failed to download $model_name"
    fi
done

### 9. Configure WebUI settings ###
echo "⚙️  Configuring WebUI settings..."
cd $AUTOMATIC1111_DIR

# Create webui-user.sh with optimized settings
cat > webui-user.sh << 'EOF'
#!/bin/bash
export COMMANDLINE_ARGS="--api --listen --port 7860 --enable-insecure-extension-access --xformers --opt-split-attention --no-half-vae"
export CUDA_VISIBLE_DEVICES=0
EOF

chmod +x webui-user.sh

# Create config.json for WebUI
mkdir -p config
cat > config/config.json << 'EOF'
{
    "samples_save": true,
    "samples_format": "png",
    "samples_filename_pattern": "[datetime]_[model_name]_[prompt_spaces]",
    "grid_save": true,
    "return_grid": true,
    "save_images_before_face_restoration": false,
    "save_images_before_highres_fix": false,
    "save_images_before_color_correction": false,
    "jpeg_quality": 80,
    "export_for_4chan": true,
    "img_downscale_threshold": 4.0,
    "target_side_length": 4000,
    "use_original_name_batch": true,
    "use_upscaler_name_as_suffix": false,
    "save_selected_only": true,
    "do_not_add_watermark": false,
    "temp_dir": "",
    "clean_temp_dir_at_start": false
}
EOF

### 10. Create systemd service ###
echo "🔧 Creating systemd service..."
cat > /etc/systemd/system/vortex-ai.service << EOF
[Unit]
Description=VortexArtec Stable Diffusion Web UI
After=network.target nvidia-persistenced.service
Wants=nvidia-persistenced.service

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=$AUTOMATIC1111_DIR
Environment=PATH=$AI_ENV_DIR/bin:/usr/local/cuda-11.7/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
Environment=LD_LIBRARY_PATH=/usr/local/cuda-11.7/lib64:/usr/local/cuda-11.7/extras/CUPTI/lib64
Environment=CUDA_HOME=/usr/local/cuda-11.7
ExecStart=$AI_ENV_DIR/bin/python launch.py --api --listen --port 7860 --enable-insecure-extension-access --xformers --opt-split-attention --no-half-vae
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=vortex-ai
KillMode=mixed
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
EOF

# Enable and start the service
systemctl daemon-reload
systemctl enable vortex-ai.service

### 11. Create management scripts ###
echo "📜 Creating management scripts..."

# Create start script
cat > /usr/local/bin/vortex-start << 'EOF'
#!/bin/bash
systemctl start vortex-ai.service
systemctl status vortex-ai.service
EOF
chmod +x /usr/local/bin/vortex-start

# Create stop script
cat > /usr/local/bin/vortex-stop << 'EOF'
#!/bin/bash
systemctl stop vortex-ai.service
EOF
chmod +x /usr/local/bin/vortex-stop

# Create status script
cat > /usr/local/bin/vortex-status << 'EOF'
#!/bin/bash
echo "=== VortexArtec AI Service Status ==="
systemctl status vortex-ai.service
echo ""
echo "=== GPU Status ==="
nvidia-smi
echo ""
echo "=== Disk Usage ==="
df -h /mnt/vortex /opt/stable-diffusion-webui
echo ""
echo "=== Service Logs (last 20 lines) ==="
journalctl -u vortex-ai.service -n 20 --no-pager
EOF
chmod +x /usr/local/bin/vortex-status

# Create update script
cat > /usr/local/bin/vortex-update << 'EOF'
#!/bin/bash
echo "Updating VortexArtec AI..."
systemctl stop vortex-ai.service
cd /opt/stable-diffusion-webui
git pull
cd extensions/sd-webui-controlnet
git pull
systemctl start vortex-ai.service
echo "Update complete!"
EOF
chmod +x /usr/local/bin/vortex-update

### 12. Final system configuration ###
echo "🔧 Final system configuration..."

# Set up log rotation
cat > /etc/logrotate.d/vortex-ai << 'EOF'
/var/log/vortex-ai.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    sharedscripts
    postrotate
        systemctl reload vortex-ai.service > /dev/null 2>&1 || true
    endscript
}
EOF

# Create MOTD
cat > /etc/motd << 'EOF'

██╗   ██╗ ██████╗ ██████╗ ████████╗███████╗██╗  ██╗
██║   ██║██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝╚██╗██╔╝
██║   ██║██║   ██║██████╔╝   ██║   █████╗   ╚███╔╝ 
╚██╗ ██╔╝██║   ██║██╔══██╗   ██║   ██╔══╝   ██╔██╗ 
 ╚████╔╝ ╚██████╔╝██║  ██║   ██║   ███████╗██╔╝ ██╗
  ╚═══╝   ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝
                                                    
🎨 VortexArtec AI Server - Ready for Art Generation!

Management Commands:
  vortex-status  - Show service status and system info
  vortex-start   - Start the AI service
  vortex-stop    - Stop the AI service  
  vortex-update  - Update the system

Web UI: http://YOUR_SERVER_IP:7860
API: http://YOUR_SERVER_IP:7860/docs

EOF

### 13. Start the service ###
echo "🚀 Starting VortexArtec AI service..."
systemctl start vortex-ai.service

# Wait for service to start
sleep 5

### 14. Final verification ###
echo ""
echo "✅ VortexArtec AI Server Deployment Complete!"
echo "=============================================="
echo ""
echo "🔍 Service Status:"
systemctl status vortex-ai.service --no-pager -l
echo ""
echo "🌐 Access Points:"
echo "   Web UI: http://$(curl -s ifconfig.me):7860"
echo "   API Documentation: http://$(curl -s ifconfig.me):7860/docs"
echo ""
echo "📊 System Info:"
echo "   GPU: $(nvidia-smi --query-gpu=name --format=csv,noheader,nounits)"
echo "   CUDA: $(nvcc --version | grep 'release' | awk '{print $6}' | cut -c2-)"
echo "   Disk Space: $(df -h $MOUNT_POINT | tail -1 | awk '{print $4}') available"
echo ""
echo "🛠️  Management Commands:"
echo "   vortex-status  - Show detailed status"
echo "   vortex-start   - Start the service"
echo "   vortex-stop    - Stop the service"
echo "   vortex-update  - Update the system"
echo ""
echo "📝 Logs: journalctl -u vortex-ai.service -f"
echo ""
echo "🎯 Next Steps:"
echo "   1. Configure your reverse proxy/load balancer"
echo "   2. Set up SSL certificates"
echo "   3. Upload custom LoRA models to models/Lora/"
echo "   4. Test the API at http://YOUR_IP:7860/docs"
echo ""
echo "Happy art generation! 🎨✨"
