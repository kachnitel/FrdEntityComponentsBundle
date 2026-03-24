<?php

namespace Kachnitel\EntityComponentsBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * Twig extension wrapper for the Kachnitel Color Converter library.
 *
 * Provides Twig filters for color conversion and luminance detection,
 * plus Twig tests used by field display templates to safely dispatch
 * on value types before attempting {{ value }}.
 */

class UtilExtension extends AbstractExtension
{
    // TODO: ensure all date/time etc needed for _display are implemented
    public function getTests(): array
    {
        return [
            // `value is object` — lets templates guard {{ value }} against
            // non-stringable PHP objects without any PHP-side changes in callers.
            new TwigTest('object', static fn(mixed $value): bool => is_object($value)),

            // `value is datetime` — lets _display.html.twig render DateTime /
            // DateTimeImmutable values via |date filter instead of attempting
            // string coercion, which would throw for mutable DateTime objects.
            new TwigTest('datetime', static fn(mixed $value): bool => $value instanceof \DateTimeInterface),
        ];
    }
}
