#!/usr/bin/env bash

# VORTEX AI Marketplace Penetration Testing Script
# Uses OWASP ZAP for automated security testing

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_URL="${TARGET_URL:-http://localhost:8000}"
ZAP_PORT="${ZAP_PORT:-8080}"
REPORT_DIR="${SCRIPT_DIR}/reports"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
log() {
    echo -e "${GREEN}[PEN-TEST]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Create reports directory
mkdir -p "$REPORT_DIR"

log "Starting VORTEX AI Marketplace penetration test"
log "Target URL: $TARGET_URL"
log "ZAP Port: $ZAP_PORT"
log "Report Directory: $REPORT_DIR"

# Check if ZAP is available
if ! command -v zap-cli &> /dev/null && ! command -v zap.sh &> /dev/null; then
    error "OWASP ZAP is not installed. Please install it first."
fi

# Function to start ZAP daemon
start_zap() {
    log "Starting OWASP ZAP daemon..."
    
    if command -v zap.sh &> /dev/null; then
        zap.sh -daemon -port "$ZAP_PORT" -config api.disablekey=true &
    else
        zap-cli --zap-path /usr/share/zaproxy/zap.sh start --start-options '-daemon -port '"$ZAP_PORT"' -config api.disablekey=true' &
    fi
    
    ZAP_PID=$!
    sleep 10
    
    # Wait for ZAP to start
    for i in {1..30}; do
        if curl -s "http://localhost:$ZAP_PORT" > /dev/null 2>&1; then
            log "ZAP daemon started successfully"
            return 0
        fi
        sleep 2
    done
    
    error "Failed to start ZAP daemon"
}

# Function to stop ZAP daemon
stop_zap() {
    log "Stopping OWASP ZAP daemon..."
    if [ -n "${ZAP_PID:-}" ]; then
        kill $ZAP_PID 2>/dev/null || true
    fi
    
    # Also try to stop via API
    curl -s "http://localhost:$ZAP_PORT/JSON/core/action/shutdown/" > /dev/null 2>&1 || true
    
    sleep 5
    log "ZAP daemon stopped"
}

# Function to run spider scan
run_spider_scan() {
    log "Running spider scan on $TARGET_URL..."
    
    # Start spider
    SPIDER_ID=$(curl -s "http://localhost:$ZAP_PORT/JSON/spider/action/scan/?url=$TARGET_URL" | jq -r '.scan')
    
    if [ "$SPIDER_ID" = "null" ]; then
        error "Failed to start spider scan"
    fi
    
    # Wait for spider to complete
    while true; do
        STATUS=$(curl -s "http://localhost:$ZAP_PORT/JSON/spider/view/status/?scanId=$SPIDER_ID" | jq -r '.status')
        if [ "$STATUS" = "100" ]; then
            log "Spider scan completed"
            break
        fi
        echo -n "."
        sleep 5
    done
    echo
}

# Function to run active scan
run_active_scan() {
    log "Running active security scan on $TARGET_URL..."
    
    # Start active scan
    SCAN_ID=$(curl -s "http://localhost:$ZAP_PORT/JSON/ascan/action/scan/?url=$TARGET_URL" | jq -r '.scan')
    
    if [ "$SCAN_ID" = "null" ]; then
        error "Failed to start active scan"
    fi
    
    # Wait for scan to complete
    while true; do
        STATUS=$(curl -s "http://localhost:$ZAP_PORT/JSON/ascan/view/status/?scanId=$SCAN_ID" | jq -r '.status')
        if [ "$STATUS" = "100" ]; then
            log "Active scan completed"
            break
        fi
        echo -n "."
        sleep 10
    done
    echo
}

# Function to run passive scan
run_passive_scan() {
    log "Running passive security scan..."
    
    # Enable all passive scan rules
    curl -s "http://localhost:$ZAP_PORT/JSON/pscan/action/enableAllScanners/" > /dev/null
    
    # Wait for passive scan to complete
    while true; do
        RECORDS=$(curl -s "http://localhost:$ZAP_PORT/JSON/pscan/view/recordsToScan/" | jq -r '.recordsToScan')
        if [ "$RECORDS" = "0" ]; then
            log "Passive scan completed"
            break
        fi
        echo -n "."
        sleep 5
    done
    echo
}

# Function to generate reports
generate_reports() {
    log "Generating security reports..."
    
    # HTML Report
    curl -s "http://localhost:$ZAP_PORT/OTHER/core/other/htmlreport/" > "$REPORT_DIR/security_report_$TIMESTAMP.html"
    
    # JSON Report
    curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/" > "$REPORT_DIR/security_report_$TIMESTAMP.json"
    
    # XML Report
    curl -s "http://localhost:$ZAP_PORT/OTHER/core/other/xmlreport/" > "$REPORT_DIR/security_report_$TIMESTAMP.xml"
    
    log "Reports generated in $REPORT_DIR"
}

# Function to analyze results
analyze_results() {
    log "Analyzing security test results..."
    
    ALERTS=$(curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/" | jq '.alerts | length')
    HIGH_ALERTS=$(curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?risk=High" | jq '.alerts | length')
    MEDIUM_ALERTS=$(curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?risk=Medium" | jq '.alerts | length')
    LOW_ALERTS=$(curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?risk=Low" | jq '.alerts | length')
    
    info "Security Test Results:"
    info "====================="
    info "Total Alerts: $ALERTS"
    info "High Risk: $HIGH_ALERTS"
    info "Medium Risk: $MEDIUM_ALERTS"
    info "Low Risk: $LOW_ALERTS"
    
    # Create summary report
    cat > "$REPORT_DIR/summary_$TIMESTAMP.txt" << EOF
VORTEX AI Marketplace Security Test Summary
==========================================
Test Date: $(date)
Target URL: $TARGET_URL
Total Alerts: $ALERTS
High Risk Alerts: $HIGH_ALERTS
Medium Risk Alerts: $MEDIUM_ALERTS
Low Risk Alerts: $LOW_ALERTS

Scan Types Performed:
- Spider Scan: ✓
- Active Scan: ✓
- Passive Scan: ✓

Report Files:
- HTML Report: security_report_$TIMESTAMP.html
- JSON Report: security_report_$TIMESTAMP.json
- XML Report: security_report_$TIMESTAMP.xml
EOF
    
    # Check for high-risk issues
    if [ "$HIGH_ALERTS" -gt 0 ]; then
        warn "High-risk security issues found! Review the reports immediately."
        return 1
    elif [ "$MEDIUM_ALERTS" -gt 5 ]; then
        warn "Multiple medium-risk security issues found. Review recommended."
        return 1
    fi
    
    log "Security test completed successfully"
    return 0
}

# Function to test specific VORTEX endpoints
test_vortex_endpoints() {
    log "Testing VORTEX-specific endpoints..."
    
    ENDPOINTS=(
        "/wp-json/vortex/v1/thorius/query"
        "/wp-json/vortex/v1/huraii/generate"
        "/wp-json/vortex/v1/cloe/analyze"
        "/wp-json/vortex/v1/agents/status"
        "/wp-json/vortex/v1/artwork/create"
        "/wp-json/vortex/v1/user/profile"
        "/wp-admin/admin-ajax.php"
    )
    
    for endpoint in "${ENDPOINTS[@]}"; do
        info "Testing endpoint: $endpoint"
        
        # Add URL to ZAP context
        curl -s "http://localhost:$ZAP_PORT/JSON/core/action/accessUrl/?url=$TARGET_URL$endpoint" > /dev/null
        
        # Test for common vulnerabilities
        curl -s "http://localhost:$ZAP_PORT/JSON/ascan/action/scanAsUser/?url=$TARGET_URL$endpoint" > /dev/null
        
        sleep 2
    done
    
    log "VORTEX endpoint testing completed"
}

# Function to test authentication
test_authentication() {
    log "Testing authentication mechanisms..."
    
    # Test login endpoints
    LOGIN_ENDPOINTS=(
        "/wp-login.php"
        "/wp-admin/"
        "/wp-json/vortex/v1/auth/login"
    )
    
    for endpoint in "${LOGIN_ENDPOINTS[@]}"; do
        info "Testing authentication at: $endpoint"
        curl -s "http://localhost:$ZAP_PORT/JSON/core/action/accessUrl/?url=$TARGET_URL$endpoint" > /dev/null
        sleep 1
    done
    
    log "Authentication testing completed"
}

# Trap to ensure ZAP is stopped on exit
trap stop_zap EXIT

# Main execution
main() {
    log "Initializing penetration testing suite..."
    
    # Check if target is accessible
    if ! curl -s "$TARGET_URL" > /dev/null 2>&1; then
        error "Target URL $TARGET_URL is not accessible"
    fi
    
    # Start ZAP
    start_zap
    
    # Run scans
    run_spider_scan
    test_vortex_endpoints
    test_authentication
    run_passive_scan
    run_active_scan
    
    # Generate reports
    generate_reports
    
    # Analyze results
    if analyze_results; then
        log "✅ Penetration testing completed successfully"
        exit 0
    else
        warn "⚠️  Security issues detected - review reports"
        exit 1
    fi
}

# Run main function
main "$@" 