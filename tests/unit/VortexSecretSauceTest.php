<?php
use PHPUnit\Framework\TestCase;

class VortexSecretSauceTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-secret-sauce.php';
        $obj = new Vortex_Secret_Sauce();
        $this->assertInstanceOf(Vortex_Secret_Sauce::class, $obj);
    }
} 