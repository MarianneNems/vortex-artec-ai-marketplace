<?php
use PHPUnit\Framework\TestCase;

class VortexMarketplaceTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-marketplace.php';
        $obj = new Vortex_Marketplace();
        $this->assertInstanceOf(Vortex_Marketplace::class, $obj);
    }
} 