#!/bin/bash
"""
VORTEX ARTEC - Secure RunPod Deployment Script
VORTEX AI ENGINE DEPLOYMENT - CONFIDENTIAL
Created by: Marianne Nems

Deploys the Vortex AI Engine with Seed Art & Zodiac analysis to RunPod
with maximum security isolation and encryption.

SECURITY LEVEL: MAXIMUM
ACCESS: Admin Only
"""

set -euo pipefail

echo "🔒 VortexArtec Vortex AI Engine Deployment Starting..."
echo "======================================================"

# Security validation
if [[ -z "${VORTEX_ADMIN_SECRET_KEY:-}" ]]; then
    echo "❌ CRITICAL: VORTEX_ADMIN_SECRET_KEY not set"
    exit 1
fi

if [[ -z "${VORTEX_ENCRYPTION_KEY:-}" ]]; then
    echo "❌ CRITICAL: VORTEX_ENCRYPTION_KEY not set"
    exit 1
fi

# Set secure environment
export RUNPOD_SECURE_MODE=true
export DEBIAN_FRONTEND=noninteractive

# Create secure directories
echo "🛡️ Creating secure directory structure..."
mkdir -p /secure/{logs,data,keys,modules}
chmod 700 /secure
chmod 700 /secure/*

# Install security dependencies
echo "🔐 Installing security packages..."
apt-get update -qq
apt-get install -y -qq \
    python3-pip \
    python3-venv \
    redis-server \
    nginx \
    ufw \
    fail2ban \
    cryptsetup \
    gnupg

# Configure firewall
echo "🔥 Configuring firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 8443/tcp  # Secure API port
ufw --force enable

# Create Python virtual environment
echo "🐍 Setting up secure Python environment..."
python3 -m venv /secure/venv
source /secure/venv/bin/activate

# Install Python dependencies
pip install --upgrade pip
pip install \
    flask \
    flask-cors \
    opencv-python \
    numpy \
    torch \
    torchvision \
    scikit-learn \
    pillow \
    redis \
    cryptography \
    pyjwt \
    psutil

# Copy Vortex AI Engine files
echo "📦 Deploying Vortex AI Engine..."
cp -r vortex_ai_engine/* /secure/modules/

# Set strict permissions
chmod -R 700 /secure/modules
chown -R root:root /secure/modules

# Configure Redis for secure storage
echo "🗄️ Configuring secure Redis..."
cat > /etc/redis/redis-secure.conf << 'EOF'
port 0
unixsocket /secure/redis.sock
unixsocketperm 700
requirepass REDIS_SECURE_PASSWORD_PLACEHOLDER
databases 16
save 900 1
save 300 10
save 60 10000
dir /secure/data
logfile /secure/logs/redis.log
loglevel notice
EOF

# Replace placeholder with actual password
REDIS_PASSWORD=$(openssl rand -base64 32)
sed -i "s/REDIS_SECURE_PASSWORD_PLACEHOLDER/${REDIS_PASSWORD}/g" /etc/redis/redis-secure.conf

# Start Redis with secure config
redis-server /etc/redis/redis-secure.conf --daemonize yes

# Create systemd service for Vortex AI Engine
echo "⚙️ Creating secure service..."
cat > /etc/systemd/system/vortex-ai-engine.service << 'EOF'
[Unit]
Description=VortexArtec Vortex AI Engine
After=network.target redis.service
Requires=redis.service

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/secure/modules
Environment=PATH=/secure/venv/bin:/usr/local/bin:/usr/bin:/bin
Environment=VORTEX_ADMIN_SECRET_KEY=SECRET_KEY_PLACEHOLDER
Environment=VORTEX_ENCRYPTION_KEY=ENCRYPTION_KEY_PLACEHOLDER
Environment=RUNPOD_SECURE_MODE=true
Environment=SECURE_API_PORT=8443
Environment=REDIS_PASSWORD=REDIS_PASSWORD_PLACEHOLDER
ExecStart=/secure/venv/bin/python api/secure_endpoints.py
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=vortex-ai-engine
KillMode=mixed
TimeoutStopSec=30

# Security hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/secure
CapabilityBoundingSet=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
EOF

# Replace placeholders with actual values
sed -i "s/SECRET_KEY_PLACEHOLDER/${VORTEX_ADMIN_SECRET_KEY}/g" /etc/systemd/system/vortex-ai-engine.service
sed -i "s/ENCRYPTION_KEY_PLACEHOLDER/${VORTEX_ENCRYPTION_KEY}/g" /etc/systemd/system/vortex-ai-engine.service
sed -i "s/REDIS_PASSWORD_PLACEHOLDER/${REDIS_PASSWORD}/g" /etc/systemd/system/vortex-ai-engine.service

# Configure Nginx reverse proxy with SSL
echo "🌐 Configuring secure web server..."
cat > /etc/nginx/sites-available/vortex-private << 'EOF'
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name secure.vortexartec.com;

    # SSL Configuration
    ssl_certificate /secure/keys/server.crt;
    ssl_certificate_key /secure/keys/server.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header Referrer-Policy no-referrer;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/m;
    limit_req zone=api burst=5 nodelay;

    # Proxy to private module
    location /api/ {
        proxy_pass http://127.0.0.1:8443;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Block all other requests
    location / {
        return 404;
    }
}

# Block HTTP requests
server {
    listen 80;
    listen [::]:80;
    server_name _;
    return 444;
}
EOF

# Generate self-signed SSL certificate (replace with real cert in production)
echo "🔑 Generating SSL certificates..."
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /secure/keys/server.key \
    -out /secure/keys/server.crt \
    -subj "/C=US/ST=State/L=City/O=VortexArtec/CN=secure.vortexartec.com"

chmod 600 /secure/keys/server.key
chmod 644 /secure/keys/server.crt

# Enable Nginx site
ln -sf /etc/nginx/sites-available/vortex-private /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Configure fail2ban for additional security
echo "🛡️ Configuring intrusion detection..."
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[nginx-http-auth]
enabled = true
port = https,http
logpath = /var/log/nginx/error.log

[vortex-private]
enabled = true
port = 8443
logpath = /secure/logs/api_access.log
maxretry = 5
findtime = 300
bantime = 1800
filter = vortex-private
EOF

# Create fail2ban filter for private module
cat > /etc/fail2ban/filter.d/vortex-private.conf << 'EOF'
[Definition]
failregex = ^.*SECURITY.*<HOST>.*$
ignoreregex =
EOF

# Create management scripts
echo "📜 Creating management scripts..."

# Start script
cat > /usr/local/bin/vortex-private-start << 'EOF'
#!/bin/bash
systemctl start redis
systemctl start vortex-private-module
systemctl start nginx
systemctl start fail2ban
systemctl status vortex-private-module
EOF
chmod +x /usr/local/bin/vortex-private-start

# Stop script
cat > /usr/local/bin/vortex-private-stop << 'EOF'
#!/bin/bash
systemctl stop vortex-private-module
systemctl stop nginx
systemctl stop fail2ban
EOF
chmod +x /usr/local/bin/vortex-private-stop

# Status script
cat > /usr/local/bin/vortex-private-status << 'EOF'
#!/bin/bash
echo "=== VortexArtec Private Module Status ==="
echo "Module Service:"
systemctl status vortex-private-module --no-pager -l
echo ""
echo "Nginx Status:"
systemctl status nginx --no-pager -l
echo ""
echo "Redis Status:"
systemctl status redis --no-pager -l
echo ""
echo "Security Status:"
systemctl status fail2ban --no-pager -l
echo ""
echo "Recent Access Logs:"
tail -20 /secure/logs/api_access.log 2>/dev/null || echo "No logs yet"
EOF
chmod +x /usr/local/bin/vortex-private-status

# Enable and start services
echo "🚀 Starting secure services..."
systemctl daemon-reload
systemctl enable redis
systemctl enable vortex-private-module
systemctl enable nginx
systemctl enable fail2ban

systemctl start redis
systemctl start vortex-private-module
systemctl start nginx
systemctl start fail2ban

# Wait for services to start
sleep 5

# Final security check
echo "🔍 Running security validation..."
if systemctl is-active --quiet vortex-private-module; then
    echo "✅ Private module service is running"
else
    echo "❌ Private module service failed to start"
    exit 1
fi

if systemctl is-active --quiet nginx; then
    echo "✅ Nginx is running"
else
    echo "❌ Nginx failed to start"
    exit 1
fi

# Display final status
echo ""
echo "✅ VortexArtec Private Module Deployment Complete!"
echo "================================================="
echo ""
echo "🔒 Security Features Enabled:"
echo "   ✓ Admin-only authentication"
echo "   ✓ Encrypted data storage"
echo "   ✓ SSL/TLS encryption"
echo "   ✓ Rate limiting"
echo "   ✓ Intrusion detection"
echo "   ✓ Firewall protection"
echo ""
echo "🌐 Access Points:"
echo "   Secure API: https://$(curl -s ifconfig.me):443/api/v1/"
echo "   Health Check: https://$(curl -s ifconfig.me):443/api/v1/health"
echo ""
echo "🛠️ Management Commands:"
echo "   vortex-private-start   - Start all services"
echo "   vortex-private-stop    - Stop all services"
echo "   vortex-private-status  - Show system status"
echo ""
echo "📊 Monitoring:"
echo "   Access Logs: /secure/logs/api_access.log"
echo "   Security Logs: /var/log/fail2ban.log"
echo ""
echo "🔐 SECURITY NOTICE:"
echo "   This module contains proprietary algorithms."
echo "   Access is restricted to authorized admin users only."
echo "   All access attempts are logged and monitored."
echo ""
echo "🎯 Next Steps:"
echo "   1. Configure DNS to point to this server"
echo "   2. Replace self-signed SSL with production certificate"
echo "   3. Test admin authentication and API endpoints"
echo "   4. Monitor security logs for any intrusion attempts"
echo ""
echo "Private module deployment complete! 🎨✨" 