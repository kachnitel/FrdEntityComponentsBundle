<?php

namespace Frd\EntityComponentsBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ColorConverterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('hex2rgb', [$this, 'hex2rgb']),
            new TwigFilter('hex2hsl', [$this, 'hex2hsl']),
            new TwigFilter('isLight', [$this, 'isLight'])
        ];
    }

    public function hex2rgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        $rgb = [];
        for ($i = 0; $i < $length; $i += 2) {
            $rgb[] = hexdec(substr($hex, $i, 2));
        }
        return array_combine(['r', 'g', 'b'], $rgb);
    }

    public function hex2hsl(string $hex): array
    {
        $rgb = $this->hex2rgb($hex);

        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $l = ($max + $min) / 2;
        $h = $s = 0;
        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }
        return [
            'h' => round($h * 360),
            's' => round($s * 100),
            'l' => round($l * 100)
        ];
    }

    /**
     * Calculate light or dark color based on the luminance of the given color.
     */
    public function isLight(string $hex, int $threshold = 55): bool
    {
        $luminance = $this->hex2hsl($hex)['l'];
        return $luminance > $threshold;
    }
}
