/**
 * VORTEX Automation Testing Interface
 */
jQuery(document).ready(function($) {
    
    const VortexTester = {
        init: function() {
            this.bindEvents();
            this.startMonitoring();
        },
        
        bindEvents: function() {
            $('#vortex-run-test').on('click', this.runTest.bind(this));
            $('#vortex-check-health').on('click', this.checkHealth.bind(this));
        },
        
        runTest: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_run_automation_tests',
                    nonce: vortex_nonce
                },
                success: function(response) {
                    $('#test-results').html(JSON.stringify(response.data, null, 2));
                }
            });
        },
        
        checkHealth: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_system_status',
                    nonce: vortex_nonce
                },
                success: function(response) {
                    $('#system-status').html(JSON.stringify(response.data, null, 2));
                }
            });
        },
        
        startMonitoring: function() {
            setInterval(this.checkHealth.bind(this), 30000);
        }
    };
    
    VortexTester.init();
}); 