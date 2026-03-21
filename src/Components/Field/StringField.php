<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Inline-editable field for string/text properties.
 *
 * $currentValue is only populated when editMode is true. In display mode the
 * template uses readValue() via #[ExposeInTemplate('value')] on the base class.
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:String :entity="user" property="name" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:String', template: '@KachnitelEntityComponents/components/field/StringField.html.twig')]
final class StringField extends AbstractEditableField
{
    #[LiveProp(writable: true, hydrateWith: 'hydrateCurrentValue', dehydrateWith: 'dehydrateCurrentValue')]
    public ?string $currentValue = null;

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);

        // Only populate currentValue in edit mode. Display mode reads the value
        // directly via readValue() / the 'value' template variable.
        if ($this->editMode) {
            $raw                = $this->readValue();
            $this->currentValue = $raw !== null ? (string) $raw : null;
        }
    }

    // ── Hydration ──────────────────────────────────────────────────────────────

    public function hydrateCurrentValue(mixed $data): ?string
    {
        return $data !== null ? (string) $data : null;
    }

    public function dehydrateCurrentValue(?string $value): ?string
    {
        return $value;
    }

    // ── LiveActions ────────────────────────────────────────────────────────────

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();
        $raw                = $this->readValue();
        $this->currentValue = $raw !== null ? (string) $raw : null;
    }

    // ── Template method ────────────────────────────────────────────────────────

    protected function persistEdit(): void
    {
        $this->writeValue($this->currentValue);
    }
}
