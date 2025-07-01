# VortexArtec AI - Quick Start Deployment

## 🚀 One-Command Deployment

```bash
# 1. Set your AWS credentials
export AWS_ACCESS_KEY_ID="your-aws-key"
export AWS_SECRET_ACCESS_KEY="your-aws-secret"

# 2. Deploy (takes 15-30 minutes)
sudo ./deploy_vortex.sh
```

## ✅ What You Get

- **Complete AI Art Server** running on port 7860
- **AUTOMATIC1111 WebUI** with ControlNet extensions
- **SDXL + SD 1.5** models pre-installed
- **Automatic startup** service configured
- **UFW firewall** properly configured
- **AWS S3** integration ready
- **Management commands** (`vortex-status`, `vortex-start`, etc.)

## 🔧 Server Requirements

- Ubuntu 20.04+ with NVIDIA GPU (8GB+ VRAM)
- 16GB+ RAM, 100GB+ storage
- Public IP with ports 22, 80, 443, 7860 open

## 🌐 Access Your Server

After deployment completes:

- **Web UI**: `http://YOUR_SERVER_IP:7860`
- **API Docs**: `http://YOUR_SERVER_IP:7860/docs`

## 📱 Management Commands

```bash
vortex-status   # Show system status
vortex-start    # Start AI service
vortex-stop     # Stop AI service
vortex-update   # Update system
```

## 🔍 Check Status

```bash
# Service status
systemctl status vortex-ai.service

# View logs
journalctl -u vortex-ai.service -f

# GPU status
nvidia-smi
```

## ⚡ Test API

```bash
curl -X POST "http://localhost:7860/sdapi/v1/txt2img" \
  -H "Content-Type: application/json" \
  -d '{"prompt": "beautiful landscape", "steps": 20}'
```

## 🆘 Troubleshooting

**Service won't start?**
```bash
journalctl -u vortex-ai.service -n 50
```

**GPU issues?**
```bash
nvidia-smi
nvcc --version
```

**Need help?** Check the full documentation in `DEPLOY-TO-WEBSITE.md`

---

**Your VortexArtec AI server is ready! 🎨✨** 