<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Twig;

use Kachnitel\EntityComponentsBundle\Twig\ColorConverterExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorConverterExtension::class)]
#[Group('twig')]
class ColorConverterExtensionTest extends TestCase
{
    private ColorConverterExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new ColorConverterExtension();
    }

    // ── getFilters() ──────────────────────────────────────────────────────────

    public function testGetFiltersRegistersThreeFilters(): void
    {
        $names = array_map(fn($f) => $f->getName(), $this->ext->getFilters());

        $this->assertContains('hex2rgb', $names);
        $this->assertContains('hex2hsl', $names);
        $this->assertContains('isLight', $names);
    }

    // ── hex2rgb() ─────────────────────────────────────────────────────────────

    public function testHex2RgbConvertsWhite(): void
    {
        $result = $this->ext->hex2rgb('ffffff');

        $this->assertSame(255, $result['r'] ?? $result[0]);
        $this->assertSame(255, $result['g'] ?? $result[1]);
        $this->assertSame(255, $result['b'] ?? $result[2]);
    }

    public function testHex2RgbConvertsBlack(): void
    {
        $result = $this->ext->hex2rgb('000000');

        $this->assertSame(0, $result['r'] ?? $result[0]);
        $this->assertSame(0, $result['g'] ?? $result[1]);
        $this->assertSame(0, $result['b'] ?? $result[2]);
    }

    public function testHex2RgbConvertsPureRed(): void
    {
        $result = $this->ext->hex2rgb('ff0000');

        $this->assertSame(255, $result['r'] ?? $result[0]);
        $this->assertSame(0, $result['g'] ?? $result[1]);
        $this->assertSame(0, $result['b'] ?? $result[2]);
    }

    public function testHex2RgbReturnsArray(): void
    {
        $this->assertIsArray($this->ext->hex2rgb('1a2b3c'));
    }

    // ── hex2hsl() ─────────────────────────────────────────────────────────────

    public function testHex2HslConvertsWhiteToZeroSaturationFullLightness(): void
    {
        $result = $this->ext->hex2hsl('ffffff');

        $lightness  = $result['l'] ?? $result[2];
        $saturation = $result['s'] ?? $result[1];

        $this->assertEqualsWithDelta(100, $lightness, 1.0);
        $this->assertEqualsWithDelta(0, $saturation, 1.0);
    }

    public function testHex2HslConvertsBlackToZeroLightness(): void
    {
        $result    = $this->ext->hex2hsl('000000');
        $lightness = $result['l'] ?? $result[2];

        $this->assertEqualsWithDelta(0, $lightness, 1.0);
    }

    public function testHex2HslReturnsArray(): void
    {
        $this->assertIsArray($this->ext->hex2hsl('3498db'));
    }

    // ── isLight() ─────────────────────────────────────────────────────────────

    /**
     * isLight() compares HSL lightness against the threshold (default 55%).
     *
     * Colour → HSL lightness reference:
     *   #ffffff (white)       → L=100%  → light  ✓
     *   #000000 (black)       → L=0%    → dark   ✓
     *   #eeeeee (light grey)  → L=93%   → light  ✓
     *   #001f3f (dark navy)   → L=12%   → dark   ✓
     *   #ffff00 (yellow)      → L=50%   → 50 < 55 → dark ✓
     *   #87ceeb (sky blue)    → L=72%   → light  ✓
     *
     * @return array<string, array{string, bool}>
     */
    public static function isLightProvider(): array
    {
        return [
            'white is light'           => ['ffffff', true],
            'black is dark'            => ['000000', false],
            'light grey is light'      => ['eeeeee', true],
            'dark navy is dark'        => ['001f3f', false],
            'yellow is dark (L=50%)'   => ['ffff00', false],
            'sky blue is light (L=72%)' => ['87ceeb', true],
        ];
    }

    #[DataProvider('isLightProvider')]
    public function testIsLightClassifiesColors(string $hex, bool $expected): void
    {
        $this->assertSame($expected, $this->ext->isLight($hex));
    }

    public function testIsLightRespectsCustomThreshold(): void
    {
        // Yellow is L=50%; threshold 40 → 50 > 40 → light
        $this->assertTrue($this->ext->isLight('ffff00', 40));

        // Yellow is L=50%; threshold 60 → 50 < 60 → dark
        $this->assertFalse($this->ext->isLight('ffff00', 60));
    }

    public function testIsLightThresholdBoundary(): void
    {
        // White (L=100%) is light even at maximum threshold
        $this->assertTrue($this->ext->isLight('ffffff', 99));

        // Black (L=0%) is dark even at minimum threshold
        $this->assertFalse($this->ext->isLight('000000', 1));
    }
}
