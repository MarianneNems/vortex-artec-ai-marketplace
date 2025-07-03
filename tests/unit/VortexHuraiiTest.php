<?php
use PHPUnit\Framework\TestCase;

class VortexHuraiiTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-huraii.php';
        $obj = new Vortex_Huraii();
        $this->assertInstanceOf(Vortex_Huraii::class, $obj);
    }
} 