<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Editable field component for PHP backed enum types.
 *
 * Renders enum values as a dropdown select in edit mode.
 * Supports both unit enums and backed enums (string or int).
 *
 * ## Custom labels
 *
 * If your enum implements a `label()` or `getLabel()` method, it will be used
 * for the dropdown option text. Otherwise the enum case name is humanized
 * (e.g. "PENDING_APPROVAL" → "Pending Approval").
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:Enum :entity="order" property="status" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:Enum', template: '@KachnitelEntityComponents/components/field/EnumField.html.twig')]
class EnumField extends AbstractEditableField
{
    use Traits\PropertyInfoTrait;

    #[LiveProp(writable: true)]
    public ?string $selectedValue = null;

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);

        if ($this->editMode) {
            $currentValue = $this->readValue();

            if ($currentValue === null) {
                $this->selectedValue = null;
            } elseif ($currentValue instanceof \BackedEnum) {
                $this->selectedValue = (string) $currentValue->value;
            } elseif (is_string($currentValue)) {
                $this->selectedValue = $currentValue;
            } else {
                throw new \UnexpectedValueException('Unexpected current value for Enum. String or BackedEnum expected.');
            }
        }
    }

    /**
     * Get all possible enum cases for the dropdown.
     *
     * @return array<string, string> value => label
     */
    #[ExposeInTemplate]
    public function getEnumCases(): array
    {
        $enumClass = $this->getEnumClass();
        if ($enumClass === null) {
            return [];
        }

        $cases = [];
        foreach ($enumClass::cases() as $case) {
            if ($case instanceof \BackedEnum) {
                $cases[(string) $case->value] = $this->formatEnumLabel($case);
            } else {
                $cases[$case->name] = $this->formatEnumLabel($case);
            }
        }

        return $cases;
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();
        $this->selectedValue = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormFieldConfig(): array
    {
        return [
            'type'     => 'choice',
            'choices'  => $this->getEnumCases(),
            'required' => !$this->isNullable(),
        ];
    }

    // ── Template method ────────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException when the property has no backing enum class,
     *                           or when no case name matches for a unit enum
     */
    protected function persistEdit(): void
    {
        $enumClass = $this->getEnumClass();

        if ($enumClass === null) {
            throw new \RuntimeException(
                sprintf('Invalid enum type for property "%s::$%s".', $this->entityClass, $this->property)
            );
        }

        if ($this->selectedValue === null) {
            $this->writeValue(null);
        } elseif (is_subclass_of($enumClass, \BackedEnum::class)) {
            $this->writeValue($enumClass::from($this->selectedValue));
        } else {
            $matched = false;
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $this->selectedValue) {
                    $this->writeValue($case);
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                throw new \RuntimeException(
                    sprintf(
                        'Unknown case "%s" for unit enum %s. Valid cases: %s.',
                        $this->selectedValue,
                        $enumClass,
                        implode(', ', array_map(fn(\UnitEnum $case) => $case->name, $enumClass::cases())),
                    )
                );
            }
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * @return class-string<\UnitEnum>|null
     */
    private function getEnumClass(): ?string
    {
        $metadata = $this->entityManager->getClassMetadata($this->entityClass);
        if (!$metadata->hasField($this->property)) {
            return null;
        }
        /** @var string|null $enumType */
        $enumType = $metadata->getFieldMapping($this->property)->enumType ?? null;
        if ($enumType === null || !enum_exists($enumType)) {
            return null;
        }

        return $enumType;
    }

    private function formatEnumLabel(\UnitEnum $enum): string
    {
        if (method_exists($enum, 'label')) {
            return (string) $enum->label();
        }

        if (method_exists($enum, 'getLabel')) {
            return (string) $enum->getLabel();
        }

        return ucwords(strtolower(str_replace('_', ' ', $enum->name)));
    }
}
