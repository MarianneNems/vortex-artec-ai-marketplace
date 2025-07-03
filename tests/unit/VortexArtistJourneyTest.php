<?php
use PHPUnit\Framework\TestCase;

class VortexArtistJourneyTest extends TestCase {
    public function testCanInstantiate() {
        require_once __DIR__ . '/../../includes/class-vortex-artist-journey.php';
        $obj = new Vortex_Artist_Journey();
        $this->assertInstanceOf(Vortex_Artist_Journey::class, $obj);
    }
} 