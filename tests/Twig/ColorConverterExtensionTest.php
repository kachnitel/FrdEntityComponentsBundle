<?php

namespace Frd\EntityComponentsBundle\Tests\Twig;

use Frd\EntityComponentsBundle\Twig\ColorConverterExtension;
use PHPUnit\Framework\TestCase;

class ColorConverterExtensionTest extends TestCase
{
    public static function hex2hslProvider(): array
    {
        return [
            ['#000000', ['h' => 0, 's' => 0, 'l' => 0]],
            ['#ffffff', ['h' => 0, 's' => 0, 'l' => 100]],
            ['#808080', ['h' => 0, 's' => 0, 'l' => 50]],
            ['#ff0000', ['h' => 0, 's' => 100, 'l' => 50]],
            ['#00ff00', ['h' => 120, 's' => 100, 'l' => 50]],
        ];
    }

    /**
     * @dataProvider hex2hslProvider
     */
    public function testHex2hsl(string $hex, array $expected): void
    {
        $extension = new ColorConverterExtension();

        $this->assertEquals(
            $expected,
            $extension->hex2hsl($hex)
        );
    }

    public function testHex2rgb(): void
    {
        $extension = new ColorConverterExtension();

        $this->assertEquals(
            ['r' => 0, 'g' => 0, 'b' => 0],
            $extension->hex2rgb('#000000')
        );

        $this->assertEquals(
            ['r' => 255, 'g' => 255, 'b' => 255],
            $extension->hex2rgb('#ffffff')
        );
    }

    public function testIsLight(): void
    {
        $extension = new ColorConverterExtension();

        $this->assertTrue($extension->isLight('#ffffff'));
        $this->assertFalse($extension->isLight('#000000'));
        $this->assertFalse($extension->isLight('#808080'));
        $this->assertFalse($extension->isLight('#ff0000'));
        $this->assertTrue($extension->isLight('#00ff00', 49));
        $this->assertFalse($extension->isLight('#00ff00', 50));
    }
}
