# 🌟 Deploy VORTEX Integration to VortexArtec.com

## **READY TO GO LIVE? Here's Your Complete Deployment Package!**

This folder contains everything you need to transform your vortexartec.com website into the complete VORTEX ecosystem.

---

## **📁 What's in This Package**

```
deployment-package/
└── vortex-artec-integration/          ← Upload this entire folder
    ├── vortex-artec-integration.php   ← Main plugin file
    ├── vortex-artec-dashboard.php     ← AI Dashboard
    ├── wordpress-integration.php      ← WordPress integration
    ├── assets/
    │   ├── css/
    │   │   └── sacred-geometry.css    ← Sacred geometry styles
    │   └── js/
    │       └── ai-dashboard.js        ← AI Dashboard JavaScript
    ├── blockchain/
    │   └── vortex-artec-wallet-integration.js ← Wallet connection
    └── smart-contracts/
        └── VortexArtecSeedArt.sol     ← Solana smart contract
```

---

## **🚀 DEPLOYMENT STEPS**

### **STEP 1: BACKUP YOUR WEBSITE (CRITICAL!)**
- Export WordPress database
- Download all website files
- Use backup plugin (UpdraftPlus recommended)

### **STEP 2: UPLOAD THE PLUGIN**
1. **Access your website files** via:
   - FTP client (FileZilla, WinSCP)
   - cPanel File Manager
   - WordPress file manager plugin

2. **Navigate to**: `/wp-content/plugins/`

3. **Upload the entire folder**: `vortex-artec-integration`
   - Make sure ALL files are uploaded
   - Check that folder structure is maintained

### **STEP 3: ACTIVATE THE PLUGIN**
1. **Login to WordPress Admin**: `https://vortexartec.com/wp-admin`
2. **Go to Plugins** → Find "VORTEX Artec Integration"
3. **Click "Activate"**
4. **Check for success message** (no errors)

### **STEP 4: VERIFY DEPLOYMENT**
Visit your website and check:
- ✅ Sacred geometry applied (golden ratio proportions)
- ✅ Enhanced navigation menus
- ✅ AI Dashboard accessible
- ✅ Wallet connection working
- ✅ All existing content preserved

---

## **🎯 EXPECTED RESULTS**

After successful deployment, your vortexartec.com will have:

### **🌟 Enhanced Navigation**
- **VORTEX AI** → Dashboard, Orchestrator, Studio, Insights, Seed-Art
- **VORTEX MARKETPLACE** → Enhanced with Wallet, NFT, Staking
- **BLOCKCHAIN** → New section (TOLA, Contracts, Governance)

### **🎭 AI Dashboard** 
- 4 AI Agents: THORIUS, HURAII, CLOE, Business Strategist
- Real-time agent interaction
- Sacred geometry monitoring
- Seed-Art technique controls

### **🔗 Blockchain Integration**
- Phantom/Solflare wallet connection
- TOLA token balance display
- Sacred staking options
- Smart contract validation

### **📐 Sacred Geometry System**
- Golden ratio (1.618) applied to all layouts
- Fibonacci sequence spacing
- Sacred color gradients
- Continuous sacred monitoring

---

## **⚡ QUICK TROUBLESHOOTING**

**Plugin won't activate?**
- Check file permissions (755 for folders, 644 for files)
- Verify PHP version (7.4+ required)
- Check WordPress version (5.0+ required)

**Sacred geometry not showing?**
- Clear browser cache
- Clear WordPress cache
- Check CSS file is loading

**Navigation issues?**
- Go to WordPress Admin → Appearance → Menus
- Refresh permalinks: Settings → Permalinks → Save

**Need to rollback?**
- Deactivate plugin in WordPress Admin
- Restore from your backup if needed

---

## **🔧 OPTIONAL CONFIGURATION**

### **API Keys** (Add to wp-config.php):
```php
// AI Agent API Keys
define('VORTEX_OPENAI_API_KEY', 'your-openai-key');
define('VORTEX_STABILITY_API_KEY', 'your-stability-ai-key');
define('VORTEX_ANTHROPIC_API_KEY', 'your-anthropic-key');

// Blockchain Configuration
define('VORTEX_SOLANA_NETWORK', 'mainnet-beta'); // or 'devnet'
define('VORTEX_TOLA_TOKEN_ADDRESS', 'YOUR_TOKEN_ADDRESS');
```

### **Performance Optimization**:
- Enable caching plugin
- Optimize images
- Configure CDN if available

---

## **📞 SUPPORT**

If you encounter any issues:

1. **Check WordPress error log**: `/wp-content/debug.log`
2. **Browser console**: F12 → Console tab
3. **Plugin conflicts**: Deactivate other plugins temporarily
4. **Theme conflicts**: Switch to default theme temporarily

**Remember**: You have a complete backup, so you can always restore if needed!

---

## **🌟 SUCCESS CONFIRMATION**

When everything is working perfectly, you'll see:

✨ **Sacred Geometry**: Golden ratio proportions everywhere
🎭 **AI Agents**: All 4 agents responding and interactive
🔗 **Wallet Integration**: Connect button working, balances showing
⛓️ **Smart Contracts**: Sacred geometry validation active
📐 **Seed-Art Technique**: Continuously monitoring and optimizing
🌐 **Enhanced Website**: All original content + powerful new features

**Your vortexartec.com is now the complete VORTEX ecosystem!** 🚀

---

## **🎯 NEXT STEPS AFTER DEPLOYMENT**

1. **Test all features** with real users
2. **Configure API keys** for full AI functionality
3. **Deploy smart contracts** to Solana mainnet
4. **Set up analytics** to monitor sacred geometry performance
5. **Create user documentation** for new features
6. **Launch announcement** to your community

**Welcome to the future of AI-powered, blockchain-integrated, sacred geometry-optimized web experiences!** 🌟

# VortexArtec AI Server Deployment Guide

## 🚀 Quick Start

Deploy a complete VortexArtec AI server with a single command:

```bash
# 1. Set environment variables (secure method)
export AWS_ACCESS_KEY_ID="your-actual-aws-key"
export AWS_SECRET_ACCESS_KEY="your-actual-aws-secret"

# 2. Run the deployment script
sudo ./deploy_vortex.sh
```

## 📋 What This Script Does

### System Setup
- ✅ Updates Ubuntu and installs prerequisites
- ✅ Installs NVIDIA drivers and CUDA 11.7 toolkit
- ✅ Configures UFW firewall (SSH, HTTP, HTTPS, port 7860)
- ✅ Mounts 40GB network volume at `/mnt/vortex`

### AI Environment
- ✅ Creates Python 3.10 virtual environment at `/opt/ai-env`
- ✅ Installs PyTorch with CUDA support
- ✅ Installs additional ML packages (transformers, diffusers, etc.)

### Stable Diffusion Setup
- ✅ Clones AUTOMATIC1111 WebUI to `/opt/stable-diffusion-webui`
- ✅ Installs ControlNet and Additional Networks extensions
- ✅ Downloads SDXL and SD 1.5 base models
- ✅ Downloads essential ControlNet models (Canny, OpenPose, Depth)
- ✅ Configures optimized launch parameters

### Service Management
- ✅ Creates systemd service `vortex-ai.service`
- ✅ Enables auto-start on boot
- ✅ Creates management scripts (`vortex-start`, `vortex-stop`, etc.)

### AWS Integration
- ✅ Configures AWS CLI with `vortexai` profile
- ✅ Sets up S3 bucket access for `vortexartec.com-client-art`

## 🔧 Prerequisites

### Server Requirements
- **OS**: Ubuntu 20.04+ with GPU support
- **GPU**: NVIDIA GPU with 8GB+ VRAM
- **RAM**: 16GB+ recommended
- **Storage**: 100GB+ (40GB network volume + system storage)
- **Network**: Public IP with ports 22, 80, 443, 7860 accessible

### Cloud Provider Setup
Update these variables in the script for your provider:

```bash
# AWS
GPU_VOLUME_DEVICE="/dev/nvme1n1"

# Google Cloud
GPU_VOLUME_DEVICE="/dev/sdb"

# Azure
GPU_VOLUME_DEVICE="/dev/sdc"

# DigitalOcean
GPU_VOLUME_DEVICE="/dev/sda"
```

## 🔐 Security Configuration

### Environment Variables (Recommended)
```bash
export AWS_ACCESS_KEY_ID="AKIA..."
export AWS_SECRET_ACCESS_KEY="your-secret-key"
export GPU_VOLUME_DEVICE="/dev/nvme1n1"  # Optional override
```

### Direct Script Editing (Less Secure)
Edit the script directly:
```bash
nano deploy_vortex.sh
# Update lines 12-13 with your actual credentials
```

## 🚀 Deployment Steps

### 1. Prepare Your Server
```bash
# Upload the script to your GPU server
scp deploy_vortex.sh root@your-server-ip:/root/

# SSH into your server
ssh root@your-server-ip
```

### 2. Configure Variables
```bash
# Set your AWS credentials
export AWS_ACCESS_KEY_ID="your-key"
export AWS_SECRET_ACCESS_KEY="your-secret"

# Optionally override GPU device
export GPU_VOLUME_DEVICE="/dev/nvme1n1"
```

### 3. Run Deployment
```bash
chmod +x deploy_vortex.sh
sudo ./deploy_vortex.sh
```

### 4. Verify Installation
```bash
# Check service status
vortex-status

# View logs
journalctl -u vortex-ai.service -f

# Test web interface
curl http://localhost:7860
```

## 🎯 Post-Deployment

### Access Points
- **Web UI**: `http://YOUR_SERVER_IP:7860`
- **API Documentation**: `http://YOUR_SERVER_IP:7860/docs`
- **API Endpoint**: `http://YOUR_SERVER_IP:7860/sdapi/v1/`

### Management Commands
```bash
vortex-status   # Show service status and system info
vortex-start    # Start the AI service
vortex-stop     # Stop the AI service
vortex-update   # Update Stable Diffusion and extensions
```

### Upload Custom Models
```bash
# LoRA models
scp your-lora.safetensors root@server:/opt/stable-diffusion-webui/models/Lora/

# Additional Stable Diffusion models
scp your-model.safetensors root@server:/opt/stable-diffusion-webui/models/Stable-diffusion/

# Restart service to detect new models
vortex-stop && vortex-start
```

## 🔄 Production Setup

### 1. Reverse Proxy (Nginx)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    
    location / {
        proxy_pass http://127.0.0.1:7860;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 2. SSL Certificate
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com
```

### 3. Cloudflare Setup
1. Add your domain to Cloudflare
2. Set DNS A record to your server IP
3. Enable "Always Use HTTPS"
4. Configure firewall rules if needed

## 🔍 Troubleshooting

### Common Issues

**Service Won't Start**
```bash
# Check logs
journalctl -u vortex-ai.service -n 50

# Check GPU
nvidia-smi

# Check CUDA
nvcc --version
```

**Out of Memory**
```bash
# Edit service to reduce memory usage
sudo nano /etc/systemd/system/vortex-ai.service
# Add: --lowvram or --medvram to ExecStart

sudo systemctl daemon-reload
sudo systemctl restart vortex-ai.service
```

**Models Not Loading**
```bash
# Check model directory
ls -la /opt/stable-diffusion-webui/models/Stable-diffusion/

# Download models manually
cd /opt/stable-diffusion-webui/models/Stable-diffusion/
wget https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0/resolve/main/sd_xl_base_1.0.safetensors
```

### Log Locations
- **Service Logs**: `journalctl -u vortex-ai.service`
- **WebUI Logs**: `/opt/stable-diffusion-webui/outputs/`
- **System Logs**: `/var/log/syslog`

## 📊 Monitoring

### System Monitoring
```bash
# GPU usage
watch -n 1 nvidia-smi

# System resources
htop

# Disk usage
df -h

# Service status
vortex-status
```

### Performance Optimization
```bash
# Enable memory optimization
echo 'COMMANDLINE_ARGS="--api --listen --xformers --opt-split-attention --medvram"' > /opt/stable-diffusion-webui/webui-user.sh

# Restart service
systemctl restart vortex-ai.service
```

## 🔄 Updates and Maintenance

### Regular Updates
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Update Stable Diffusion
vortex-update

# Update NVIDIA drivers (if needed)
sudo ubuntu-drivers autoinstall
sudo reboot
```

### Backup Important Data
```bash
# Backup custom models
tar -czf vortex-models-backup.tar.gz /opt/stable-diffusion-webui/models/

# Backup configuration
tar -czf vortex-config-backup.tar.gz /opt/stable-diffusion-webui/config/
```

## 📈 Scaling

### Load Balancing Multiple Instances
```bash
# Deploy to multiple servers
for server in server1 server2 server3; do
    scp deploy_vortex.sh root@$server:/root/
    ssh root@$server "chmod +x deploy_vortex.sh && ./deploy_vortex.sh"
done
```

### Container Deployment
The script can be adapted for Docker deployment:
```dockerfile
FROM nvidia/cuda:11.7-devel-ubuntu20.04
COPY deploy_vortex.sh /opt/
RUN chmod +x /opt/deploy_vortex.sh && /opt/deploy_vortex.sh
```

## 🎨 API Usage Examples

### Generate Image
```bash
curl -X POST "http://YOUR_SERVER:7860/sdapi/v1/txt2img" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "a beautiful landscape, highly detailed",
    "steps": 20,
    "width": 512,
    "height": 512
  }'
```

### Python API Client
```python
import requests

url = "http://YOUR_SERVER:7860/sdapi/v1/txt2img"
payload = {
    "prompt": "VortexArtec logo, modern design",
    "steps": 30,
    "width": 1024,
    "height": 1024,
    "cfg_scale": 7
}

response = requests.post(url, json=payload)
result = response.json()
```

## 🆘 Support

- **Logs**: Always check `journalctl -u vortex-ai.service -f` first
- **GPU Issues**: Verify with `nvidia-smi` and check CUDA installation
- **Network Issues**: Verify firewall settings with `ufw status`
- **Model Issues**: Check model file integrity and paths

---

**Happy AI Art Generation! 🎨✨** 