<?php

namespace Kachnitel\EntityComponentsBundle\Twig;

use Kachnitel\ColorConverter\ColorConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Twig extension wrapper for the Kachnitel Color Converter library.
 *
 * Provides Twig filters for color conversion and luminance detection,
 * plus an `is object` test used by field display templates to safely
 * distinguish PHP objects from scalars before attempting {{ value }}.
 */
class ColorConverterExtension extends AbstractExtension
{
    private ColorConverter $converter;

    public function __construct()
    {
        $this->converter = new ColorConverter();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('hex2rgb', [$this, 'hex2rgb']),
            new TwigFilter('hex2hsl', [$this, 'hex2hsl']),
            new TwigFilter('isLight', [$this, 'isLight']),
        ];
    }

    public function getTests(): array
    {
        return [
            // `value is object` — lets templates guard {{ value }} against
            // non-stringable PHP objects without any PHP-side changes in callers.
            new TwigTest('object', static fn (mixed $value): bool => is_object($value)),
        ];
    }

    public function hex2rgb(string $hex): array
    {
        return $this->converter->hex2rgb($hex);
    }

    public function hex2hsl(string $hex): array
    {
        return $this->converter->hex2hsl($hex);
    }

    public function isLight(string $hex, int $threshold = 55): bool
    {
        return $this->converter->isLight($hex, $threshold);
    }
}
