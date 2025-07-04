# 🔒 VORTEX ARTEC - Environment Security Configuration

## 🛡️ Security-First Environment Management

This guide outlines the secure configuration of environment variables and sensitive data for VORTEX ARTEC's infrastructure.

### 🔐 Environment Variable Structure

```bash
# 🔒 Security Configuration
VORTEX_ENCRYPTION_KEY=your-32-character-encryption-key-here
VORTEX_API_SECRET=your-api-secret-key-here
VORTEX_JWT_SECRET=your-jwt-secret-key-here

# Database Security
DB_ENCRYPTION_KEY=your-database-encryption-key-here
DB_SSL_MODE=required
DB_SSL_CERT_PATH=/path/to/ssl/cert.pem
DB_SSL_KEY_PATH=/path/to/ssl/key.pem

# 🔐 API Keys & External Services
AWS_ACCESS_KEY_ID=your-aws-access-key-id
AWS_SECRET_ACCESS_KEY=your-aws-secret-access-key
AWS_REGION=us-east-1
AWS_S3_BUCKET_PUBLIC=vortex-public-assets
AWS_S3_BUCKET_PRIVATE=vortex-private-models

# OpenAI API (for AI features)
OPENAI_API_KEY=your-openai-api-key-here
OPENAI_ORGANIZATION=your-openai-org-id

# Stripe Payment Processing
STRIPE_PUBLIC_KEY=pk_test_your-stripe-public-key
STRIPE_SECRET_KEY=sk_test_your-stripe-secret-key
STRIPE_WEBHOOK_SECRET=whsec_your-webhook-secret

# 🔒 Proprietary Algorithm Access
PRIVATE_REPO_TOKEN=your-private-repo-access-token
PRIVATE_REPO_URL=https://github.com/MarianneNems/vortex-artec-private

# RunPod Vault Configuration
RUNPOD_API_KEY=your-runpod-api-key-here
RUNPOD_ENDPOINT_ID=your-runpod-endpoint-id
RUNPOD_TEMPLATE_ID=your-runpod-template-id

# AI Model Access
VORTEX_MODEL_ACCESS_TOKEN=your-model-access-token
HURAII_MODEL_KEY=your-huraii-model-key
CLOE_MODEL_KEY=your-cloe-model-key

# 🛡️ Security Features
RATE_LIMIT_REQUESTS_PER_MINUTE=100
RATE_LIMIT_ENABLED=true
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://vortexartec.com
SESSION_TIMEOUT_MINUTES=30
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE=strict

# 📊 Monitoring & Logging
LOG_LEVEL=info
LOG_FILE_PATH=/var/log/vortex/app.log
MONITORING_ENABLED=true
SENTRY_DSN=your-sentry-dsn-here
```

### 🔑 Key Generation Commands

```bash
# Generate encryption keys
openssl rand -hex 32  # For VORTEX_ENCRYPTION_KEY
openssl rand -hex 32  # For VORTEX_API_SECRET
openssl rand -hex 32  # For VORTEX_JWT_SECRET
openssl rand -hex 32  # For DB_ENCRYPTION_KEY

# Generate secure passwords
openssl rand -base64 32  # For database passwords
openssl rand -base64 32  # For service accounts

# Generate SSL certificates
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes
```

### 🛡️ Environment Security Best Practices

#### 1. **Key Management**
- ✅ Generate unique keys for each environment (dev, staging, prod)
- ✅ Use minimum 32-character keys for encryption
- ✅ Rotate keys every 90 days
- ✅ Store keys in secure key management systems (AWS Secrets Manager, HashiCorp Vault)
- ✅ Never commit keys to version control

#### 2. **Database Security**
- ✅ Enable SSL/TLS for all database connections
- ✅ Use encrypted connections only
- ✅ Implement database-level encryption
- ✅ Use separate database users with minimal permissions
- ✅ Enable audit logging

#### 3. **API Security**
- ✅ Use API keys with scoped permissions
- ✅ Implement rate limiting
- ✅ Enable CORS with strict origin policies
- ✅ Use JWT tokens with short expiration times
- ✅ Implement API key rotation

#### 4. **External Service Security**
- ✅ Use least-privilege access for AWS IAM roles
- ✅ Enable MFA for all service accounts
- ✅ Monitor API usage and set up alerts
- ✅ Use VPC endpoints for AWS services
- ✅ Implement service-to-service authentication

### 🔒 Proprietary Algorithm Protection

#### Environment Variables for Private Access
```bash
# Private Repository Access
PRIVATE_REPO_TOKEN=ghp_your_private_access_token
PRIVATE_REPO_URL=https://github.com/MarianneNems/vortex-artec-private

# RunPod Vault (for proprietary algorithms)
RUNPOD_API_KEY=your_runpod_api_key
RUNPOD_ENDPOINT_ID=your_runpod_endpoint_id
RUNPOD_VAULT_TOKEN=your_vault_access_token

# AI Model Access (proprietary models)
VORTEX_MODEL_ACCESS_TOKEN=your_model_access_token
HURAII_PRIVATE_KEY=your_huraii_private_key
CLOE_PRIVATE_KEY=your_cloe_private_key
BUSINESS_STRATEGIST_KEY=your_business_strategist_key
```

#### Access Control Configuration
```bash
# Role-Based Access Control
RBAC_ENABLED=true
ADMIN_ROLE_USERS=marianne@vortexartec.com,security@vortexartec.com
DEVELOPER_ROLE_USERS=dev@vortexartec.com
INVESTOR_ROLE_USERS=investor@vortexartec.com

# IP Whitelisting
ALLOWED_IPS=192.168.1.0/24,10.0.0.0/8
BLOCKED_IPS=

# Audit Logging
AUDIT_LOG_ENABLED=true
AUDIT_LOG_PATH=/var/log/vortex/audit.log
AUDIT_LOG_RETENTION_DAYS=365
```

### 🌍 Environment-Specific Configuration

#### Development Environment
```bash
NODE_ENV=development
VORTEX_ENV=development
DEBUG_MODE=true
LOG_LEVEL=debug
RATE_LIMIT_ENABLED=false
```

#### Staging Environment
```bash
NODE_ENV=staging
VORTEX_ENV=staging
DEBUG_MODE=false
LOG_LEVEL=info
RATE_LIMIT_ENABLED=true
```

#### Production Environment
```bash
NODE_ENV=production
VORTEX_ENV=production
DEBUG_MODE=false
LOG_LEVEL=warn
RATE_LIMIT_ENABLED=true
FORCE_HTTPS=true
HSTS_MAX_AGE=31536000
SECURE_HEADERS=true
CSP_ENABLED=true
XSS_PROTECTION=true
```

### 📊 Monitoring & Alerting

#### Security Monitoring
```bash
# Security Event Monitoring
SECURITY_MONITORING_ENABLED=true
FAILED_LOGIN_THRESHOLD=5
RATE_LIMIT_ALERT_THRESHOLD=1000
SUSPICIOUS_ACTIVITY_THRESHOLD=10

# Alert Configuration
ALERT_EMAIL=security@vortexartec.com
ALERT_SLACK_WEBHOOK=your_slack_webhook_url
ALERT_PAGERDUTY_KEY=your_pagerduty_key
```

#### Performance Monitoring
```bash
# Performance Metrics
PERFORMANCE_MONITORING_ENABLED=true
METRICS_COLLECTION_INTERVAL=60
METRICS_RETENTION_DAYS=30
SLOW_QUERY_THRESHOLD=1000

# External Monitoring
DATADOG_API_KEY=your_datadog_api_key
NEW_RELIC_LICENSE_KEY=your_new_relic_key
```

### 🔧 Configuration Management

#### Using Environment Files
1. **Create environment-specific files**:
   - `.env.development`
   - `.env.staging`
   - `.env.production`

2. **Load configuration in application**:
   ```javascript
   require('dotenv').config({
     path: `.env.${process.env.NODE_ENV}`
   });
   ```

3. **Validate required variables**:
   ```javascript
   const requiredVars = [
     'VORTEX_ENCRYPTION_KEY',
     'VORTEX_API_SECRET',
     'DB_ENCRYPTION_KEY',
     'AWS_ACCESS_KEY_ID',
     'AWS_SECRET_ACCESS_KEY'
   ];
   
   requiredVars.forEach(varName => {
     if (!process.env[varName]) {
       throw new Error(`Required environment variable ${varName} is not set`);
     }
   });
   ```

### 🚨 Security Incident Response

#### Environment Variable Compromise
1. **Immediate Actions**:
   - Revoke compromised keys immediately
   - Generate new keys using secure methods
   - Update all affected systems
   - Monitor for unauthorized access

2. **Investigation**:
   - Check audit logs for unauthorized access
   - Identify scope of compromise
   - Assess data exposure risk
   - Document incident details

3. **Recovery**:
   - Deploy new secure configuration
   - Verify system integrity
   - Update security measures
   - Notify stakeholders if required

### 📋 Security Checklist

#### Pre-Deployment Checklist
- [ ] All environment variables are set
- [ ] Keys are generated using secure methods
- [ ] SSL/TLS is enabled for all connections
- [ ] Rate limiting is configured
- [ ] Monitoring is enabled
- [ ] Audit logging is active
- [ ] Access controls are in place
- [ ] IP whitelisting is configured (if required)
- [ ] Backup and recovery procedures are tested
- [ ] Security scanning is complete

#### Regular Security Reviews
- [ ] Monthly key rotation review
- [ ] Quarterly access permission audit
- [ ] Annual security configuration review
- [ ] Incident response plan testing
- [ ] Vulnerability assessment
- [ ] Penetration testing (annually)

### 📞 Security Contacts

- **Security Team**: security@vortexartec.com
- **Incident Response**: incident@vortexartec.com
- **Emergency Contact**: marianne@vortexartec.com
- **Technical Support**: support@vortexartec.com

### 🔗 Additional Resources

- [AWS Security Best Practices](https://aws.amazon.com/security/security-resources/)
- [OWASP Security Guidelines](https://owasp.org/www-project-top-ten/)
- [Node.js Security Best Practices](https://nodejs.org/en/docs/guides/security/)
- [WordPress Security Guide](https://wordpress.org/support/article/hardening-wordpress/)

---

**Remember**: Security is an ongoing process. Regular reviews and updates of these configurations are essential for maintaining a secure environment. 