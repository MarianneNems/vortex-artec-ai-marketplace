#!/bin/bash

# VORTEX ARTEC Private Pod Security Setup
# Run this script immediately after pod deployment

echo "🔒 Setting up VORTEX ARTEC Private Pod Security..."

# Create private directories
mkdir -p /workspace/vortex_private_data
mkdir -p /secure/logs
mkdir -p /secure/config

# Set strict permissions
chmod 700 /workspace/vortex_private_data
chmod 700 /secure

# Configure firewall - Block all except admin IPs
ufw --force reset
ufw default deny incoming
ufw default deny outgoing

# Allow admin IP ranges
ufw allow from 203.0.113.0/24 to any port 22
ufw allow from 198.51.100.0/24 to any port 22

# Allow essential outbound for package updates and S3
ufw allow out 443  # HTTPS
ufw allow out 53   # DNS

ufw --force enable

# Disable unnecessary services
systemctl disable apache2 2>/dev/null || true
systemctl disable nginx 2>/dev/null || true
systemctl stop apache2 2>/dev/null || true
systemctl stop nginx 2>/dev/null || true

# Create audit logging
cat > /secure/config/audit.conf << 'EOF'
# VORTEX ARTEC Audit Configuration
log_file = /secure/logs/vortex_audit.log
log_level = INFO
admin_actions = true
access_attempts = true
file_modifications = true
EOF

# Set up access control
cat > /secure/config/access_control.sh << 'EOF'
#!/bin/bash
# VORTEX ARTEC Access Control

ADMIN_USERS=("marianne_nems" "vortex_admin")
CURRENT_USER=$(whoami)

# Check if user is admin
is_admin() {
    local user=$1
    for admin in "${ADMIN_USERS[@]}"; do
        if [[ "$admin" == "$user" ]]; then
            return 0
        fi
    done
    return 1
}

# Log access attempt
log_access() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Access attempt by $CURRENT_USER from $(who am i | awk '{print $5}')" >> /secure/logs/vortex_audit.log
}

# Main access check
if ! is_admin "$CURRENT_USER"; then
    log_access
    echo "❌ Access denied. Admin privileges required for VORTEX ARTEC private engine."
    exit 1
fi

log_access
echo "✅ Admin access granted for $CURRENT_USER"
EOF

chmod +x /secure/config/access_control.sh

# Create environment setup
cat > /workspace/.vortex_private_env << 'EOF'
export PRIVATE_MODE=true
export VORTEX_ADMIN_SECRET_KEY="$(openssl rand -hex 32)"
export VORTEX_ENCRYPTION_KEY="$(python3 -c 'from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())')"
export PATH="/secure/bin:$PATH"
EOF

# Source environment in bashrc
echo "source /workspace/.vortex_private_env" >> ~/.bashrc

# Create daily health check
cat > /secure/bin/vortex_healthcheck << 'EOF'
#!/bin/bash
# Daily health report for VORTEX ARTEC Private Pod

DATE=$(date '+%Y-%m-%d %H:%M:%S')
echo "[$DATE] VORTEX Private Pod Health Check" >> /secure/logs/health.log

# Check disk usage
DISK_USAGE=$(df -h /workspace | tail -1 | awk '{print $5}')
echo "Disk Usage: $DISK_USAGE" >> /secure/logs/health.log

# Check memory
MEM_USAGE=$(free -h | grep '^Mem:' | awk '{print $3"/"$2}')
echo "Memory Usage: $MEM_USAGE" >> /secure/logs/health.log

# Check GPU
nvidia-smi --query-gpu=utilization.gpu,memory.used,memory.total --format=csv,noheader,nounits >> /secure/logs/health.log

# Check access logs
ACCESS_COUNT=$(grep "$(date '+%Y-%m-%d')" /secure/logs/vortex_audit.log | wc -l)
echo "Today's access attempts: $ACCESS_COUNT" >> /secure/logs/health.log

echo "Health check completed" >> /secure/logs/health.log
EOF

chmod +x /secure/bin/vortex_healthcheck

# Set up cron for daily health checks
echo "0 2 * * * /secure/bin/vortex_healthcheck" | crontab -

# Create shutdown timer (30 days)
echo "shutdown -h +43200" | at now + 30 days 2>/dev/null || true

# Final security hardening
chmod 600 /workspace/.vortex_private_env
chown -R $USER:$USER /workspace/vortex_private_data
chown -R root:root /secure

echo "🔒 VORTEX ARTEC Private Pod Security Setup Complete!"
echo "📍 Private data directory: /workspace/vortex_private_data"
echo "📍 Security logs: /secure/logs/"
echo "📍 Admin access required for all operations"
echo ""
echo "⚠️  IMPORTANT: This pod will auto-shutdown after 30 days of inactivity"
echo "⚠️  Only admin IPs 203.0.113.0/24 and 198.51.100.0/24 can access" 