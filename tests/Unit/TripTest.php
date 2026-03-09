<?php

    namespace Tests\Unit;

    use App\Models\Viaje;
    use PHPUnit\Framework\TestCase;

    class TripTest extends TestCase {
        public function test_calculate_co2_saved_returns_zero_when_distance_missing(): void {
            $viaje = new Viaje();
            $viaje->distance = null;

            $this->assertSame(0, $viaje->calculateCO2Saved());
        }

        public function test_calculate_co2_saved_calculates_expected_value(): void {
            $viaje = new Viaje();
            $viaje->distance = 10;

            $expected = round((10 * 0.120) - (10 * 0.080), 2);

            $this->assertSame($expected, $viaje->calculateCO2Saved());
        }
    }