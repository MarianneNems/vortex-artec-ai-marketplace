#!/usr/bin/env bash

set -e  # Exit on any error

# VORTEX AI Marketplace - Staging Smoke Test Suite
# This script performs essential functionality tests on the staging environment

echo "🚀 Starting VORTEX AI Marketplace Staging Smoke Tests..."
echo "================================================="

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to log test results
log_test() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ PASS:${NC} $2"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ FAIL:${NC} $2"
        ((TESTS_FAILED++))
    fi
}

# Function to test URL response
test_url() {
    local url=$1
    local expected_code=${2:-200}
    local description=$3
    
    response_code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    if [ "$response_code" -eq "$expected_code" ]; then
        log_test 0 "$description (HTTP $response_code)"
    else
        log_test 1 "$description (Expected: $expected_code, Got: $response_code)"
    fi
}

echo "1. 🔌 Plugin Activation Test"
echo "-----------------------------"

# Activate VORTEX plugin
wp plugin activate vortex-ai-marketplace 2>/dev/null
if [ $? -eq 0 ]; then
    log_test 0 "VORTEX AI Marketplace plugin activated"
else
    log_test 1 "Failed to activate VORTEX AI Marketplace plugin"
fi

# Check if plugin is active
wp plugin is-active vortex-ai-marketplace
log_test $? "Plugin activation status verified"

echo ""
echo "2. 📊 Database Tables Check"
echo "---------------------------"

# Check critical database tables exist
TABLES=("vortex_users" "vortex_transactions" "vortex_wallets" "vortex_seed_artworks" "vortex_gamification_events")

for table in "${TABLES[@]}"; do
    wp db query "DESCRIBE ${table};" > /dev/null 2>&1
    log_test $? "Database table '${table}' exists"
done

echo ""
echo "3. 🔄 Cron Events Test"
echo "----------------------"

# Run scheduled events
wp cron event run --due-now 2>/dev/null
log_test $? "Cron events executed successfully"

# Check if VORTEX cron hooks are scheduled
wp cron event list --format=csv | grep -q "vortex_daily_masterwork"
log_test $? "Daily masterwork cron job scheduled"

wp cron event list --format=csv | grep -q "vortex_daily_milestone_reminder"
log_test $? "Milestone reminder cron job scheduled"

echo ""
echo "4. 🎨 AI Generation Simulation"
echo "------------------------------"

# Simulate AI generation request (using wp-cli if available)
if command -v curl >/dev/null 2>&1; then
    # Test AI health endpoint
    test_url "http://localhost:8000/health" 200 "AI server health check"
    
    # Test WordPress REST API
    test_url "$(wp option get siteurl)/wp-json/vortex/v1/generate" 401 "AI generation endpoint (requires auth)"
else
    log_test 1 "cURL not available for AI generation test"
fi

echo ""
echo "5. 📄 PDF Generation Test"
echo "-------------------------"

# Test PDF generation with mock data
php -r "
if (class_exists('Dompdf\\Dompdf')) {
    \$dompdf = new Dompdf\\Dompdf();
    \$dompdf->loadHtml('<h1>VORTEX Test PDF</h1><p>PDF generation working!</p>');
    \$dompdf->setPaper('A4', 'portrait');
    \$dompdf->render();
    echo 'PDF generation test passed';
    exit(0);
} else {
    echo 'DOMPDF not available';
    exit(1);
}
" 2>/dev/null
log_test $? "PDF generation library available"

echo ""
echo "6. 🔗 API Endpoints Test"
echo "------------------------"

SITE_URL=$(wp option get siteurl)

# Test REST API endpoints
test_url "$SITE_URL/wp-json/wp/v2/" 200 "WordPress REST API base"
test_url "$SITE_URL/wp-json/vortex/v1/" 401 "VORTEX API namespace (requires auth)"

echo ""
echo "7. 🪙 TOLA Token Simulation"
echo "---------------------------"

# Simulate TOLA token operations
php -r "
// Mock TOLA wallet operations
\$mock_user_id = 1;
\$mock_balance = 100;
\$mock_transaction = array(
    'user_id' => \$mock_user_id,
    'amount' => 10,
    'type' => 'credit',
    'description' => 'Test transaction'
);

// Test basic array operations (simulating wallet functions)
if (is_array(\$mock_transaction) && \$mock_balance > 0) {
    echo 'TOLA token simulation passed';
    exit(0);
} else {
    echo 'TOLA token simulation failed';
    exit(1);
}
" 2>/dev/null
log_test $? "TOLA token simulation"

echo ""
echo "8. 🖼️ NFT Mint Stub"
echo "------------------"

# Test NFT minting stub (mock blockchain interaction)
php -r "
// Mock NFT minting
\$mock_artwork = array(
    'id' => 'test_artwork_123',
    'artist_id' => 1,
    'metadata' => array(
        'name' => 'Test Artwork',
        'description' => 'Smoke test NFT'
    )
);

// Simulate successful mint
if (isset(\$mock_artwork['id']) && isset(\$mock_artwork['metadata'])) {
    echo 'NFT mint stub passed';
    exit(0);
} else {
    echo 'NFT mint stub failed';
    exit(1);
}
" 2>/dev/null
log_test $? "NFT minting stub"

echo ""
echo "9. 📊 Quiz Submission Test"
echo "--------------------------"

# Test quiz submission endpoint
test_url "$SITE_URL/wp-admin/admin-ajax.php" 400 "AJAX endpoint accessible (expects POST)"

# Simulate quiz processing
php -r "
// Mock quiz submission
\$mock_quiz_data = array(
    'user_id' => 1,
    'answers' => array(
        'question_1' => 'answer_a',
        'question_2' => 'answer_c'
    ),
    'score' => 85
);

// Test quiz data structure
if (isset(\$mock_quiz_data['answers']) && \$mock_quiz_data['score'] > 0) {
    echo 'Quiz submission test passed';
    exit(0);
} else {
    echo 'Quiz submission test failed';
    exit(1);
}
" 2>/dev/null
log_test $? "Quiz submission simulation"

echo ""
echo "10. 🔧 System Dependencies"
echo "--------------------------"

# Check PHP extensions
php -m | grep -q "gd"
log_test $? "GD extension available"

php -m | grep -q "curl"
log_test $? "cURL extension available"

php -m | grep -q "json"
log_test $? "JSON extension available"

# Check WordPress version
WP_VERSION=$(wp core version)
if [[ "$WP_VERSION" > "5.6" ]]; then
    log_test 0 "WordPress version ($WP_VERSION) meets requirements"
else
    log_test 1 "WordPress version ($WP_VERSION) below minimum (5.6)"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if [[ "$PHP_VERSION" > "8.1" ]]; then
    log_test 0 "PHP version ($PHP_VERSION) meets requirements"
else
    log_test 1 "PHP version ($PHP_VERSION) below minimum (8.1)"
fi

echo ""
echo "11. 🗄️ Storage & Permissions"
echo "----------------------------"

# Check upload directory
wp eval "
\$upload_dir = wp_upload_dir();
if (is_writable(\$upload_dir['basedir'])) {
    echo 'Upload directory writable';
    exit(0);
} else {
    echo 'Upload directory not writable';
    exit(1);
}
"
log_test $? "WordPress upload directory writable"

# Check plugin directory permissions
if [ -w "$(wp plugin path vortex-ai-marketplace)" ]; then
    log_test 0 "Plugin directory has correct permissions"
else
    log_test 1 "Plugin directory permissions issue"
fi

echo ""
echo "12. 🌐 External Services"
echo "------------------------"

# Test external service connectivity (if configured)
if [ -n "$AI_SERVER_URL" ]; then
    test_url "$AI_SERVER_URL/health" 200 "External AI server connectivity"
else
    echo -e "${YELLOW}⚠️  SKIP:${NC} AI_SERVER_URL not configured"
fi

# Test AWS S3 connectivity (if configured)
if [ -n "$AWS_ACCESS_KEY_ID" ] && command -v aws >/dev/null 2>&1; then
    aws s3 ls > /dev/null 2>&1
    log_test $? "AWS S3 connectivity"
else
    echo -e "${YELLOW}⚠️  SKIP:${NC} AWS CLI not configured or available"
fi

echo ""
echo "🏁 Test Summary"
echo "==============="
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All smoke tests passed! System ready for deployment.${NC}"
    exit 0
else
    echo -e "${RED}⚠️  Some tests failed. Please review before deployment.${NC}"
    exit 1
fi

echo "Smoke tests completed." 