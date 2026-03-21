<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Inline-editable field for float/decimal properties.
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:Float :entity="product" property="price" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:Float', template: '@KachnitelEntityComponents/components/field/FloatField.html.twig')]
final class FloatField extends AbstractEditableField
{
    #[LiveProp(writable: true, hydrateWith: 'hydrateCurrentValue', dehydrateWith: 'dehydrateCurrentValue')]
    public ?float $currentValue = null;

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);
        $raw                = $this->readValue();
        $this->currentValue = $raw !== null ? (float) $raw : null;
    }

    // ── Hydration ──────────────────────────────────────────────────────────────

    public function hydrateCurrentValue(mixed $data): ?float
    {
        return $data !== null ? (float) $data : null;
    }

    public function dehydrateCurrentValue(?float $value): ?float
    {
        return $value;
    }

    // ── LiveActions ────────────────────────────────────────────────────────────

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();
        $raw                = $this->readValue();
        $this->currentValue = $raw !== null ? (float) $raw : null;
    }

    // ── Template method ────────────────────────────────────────────────────────

    protected function persistEdit(): void
    {
        $this->writeValue($this->currentValue);
    }
}
