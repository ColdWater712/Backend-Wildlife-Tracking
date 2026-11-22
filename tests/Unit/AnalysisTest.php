<?php

namespace Tests\Unit;

use App\Models\GeoZone;
use App\Services\Ai\AnalysisVocabulary;
use App\Services\Ai\AudioFeatureExtractor;
use App\Services\GeoService;
use PHPUnit\Framework\TestCase;
use Tests\Support\WavFactory;

class AnalysisTest extends TestCase
{
    public function test_vocabulary_normalizes_free_text(): void
    {
        $this->assertSame('injured', AnalysisVocabulary::condition('Terlihat pincang dan berdarah'));
        $this->assertSame('emaciated', AnalysisVocabulary::condition('very thin'));
        $this->assertSame('unknown', AnalysisVocabulary::condition(''));
        $this->assertSame('foraging', AnalysisVocabulary::behavior('eating fruit'));
        $this->assertSame('gunshot', AnalysisVocabulary::soundCategory('rifle shot'));
        $this->assertSame('chainsaw', AnalysisVocabulary::soundCategory('Gergaji mesin'));
        $this->assertSame('critical', AnalysisVocabulary::maxThreat('low', 'critical', 'none'));
    }

    public function test_wav_features_distinguish_impulse_from_sustained_sound(): void
    {
        $extractor = new AudioFeatureExtractor();
        $dir = sys_get_temp_dir();

        file_put_contents($impulse = "{$dir}/impulse.wav", WavFactory::impulse(2.0));
        file_put_contents($tone = "{$dir}/tone.wav", WavFactory::sustainedTone(2.0, 1500));

        $a = $extractor->extract($impulse);
        $b = $extractor->extract($tone);

        $this->assertGreaterThanOrEqual(1, $a['impulsive_events']);
        $this->assertGreaterThan(8, $a['crest_factor']);
        $this->assertSame(0, $b['impulsive_events']);
        $this->assertGreaterThan(0.9, $b['loud_frame_ratio']);
        $this->assertEqualsWithDelta(1500, $b['approx_dominant_hz'], 150);
        $this->assertEqualsWithDelta(2.0, $b['duration_seconds'], 0.01);
        $this->assertNull($extractor->extract(__FILE__));
    }

    public function test_geo_contains_radius_and_polygon(): void
    {
        $geo = new GeoService();

        $circle = new GeoZone(['center_lat' => 3.6, 'center_lon' => 98.0, 'radius_km' => 1.0]);
        $this->assertTrue($geo->zoneContains($circle, 3.605, 98.0));
        $this->assertFalse($geo->zoneContains($circle, 3.7, 98.0));

        $polygon = new GeoZone(['polygon_geojson' => ['type' => 'Polygon', 'coordinates' => [[[98, 3], [98.1, 3], [98.1, 3.1], [98, 3.1], [98, 3]]]]]);
        $this->assertTrue($geo->zoneContains($polygon, 3.05, 98.05));
        $this->assertFalse($geo->zoneContains($polygon, 3.2, 98.05));

        $this->assertEqualsWithDelta(111.2, $geo->distanceKm(0, 0, 1, 0), 0.2);
        $this->assertFalse($geo->isValidPolygon(['type' => 'Polygon', 'coordinates' => [[[1, 1]]]]));
    }
}
