<?php
use PHPUnit\Framework\TestCase;

class ApiEndpointTest extends TestCase {
    public function testGetAnalyticsSummary() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $response = wp_remote_get( site_url('/wp-json/vortex/v1/analytics/summary') );
        $this->assertEquals(200, wp_remote_retrieve_response_code($response));
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $this->assertArrayHasKey('summary', $data);
    }
} 