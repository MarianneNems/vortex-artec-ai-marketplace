<?php
use PHPUnit\Framework\TestCase;

class VortexSystemInitializerTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-system-initializer.php';
        $obj = new Vortex_System_Initializer();
        $this->assertInstanceOf(Vortex_System_Initializer::class, $obj);
    }
} 