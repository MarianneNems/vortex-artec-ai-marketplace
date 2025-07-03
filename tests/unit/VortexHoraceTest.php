<?php
use PHPUnit\Framework\TestCase;

class VortexHoraceTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-horace.php';
        $obj = new Vortex_Horace();
        $this->assertInstanceOf(Vortex_Horace::class, $obj);
    }
} 