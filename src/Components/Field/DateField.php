<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Editable field component for date and datetime types.
 *
 * Supports all Doctrine date column variants:
 * - `datetime` / `datetime_immutable` — HTML datetime-local input
 * - `datetimetz` / `datetimetz_immutable` — treated as datetime
 * - `date` / `date_immutable` — HTML date input
 * - `time` / `time_immutable` — HTML time input
 *
 * Null values are supported on nullable columns.
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:Date :entity="event" property="startsAt" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:Date', template: '@KachnitelEntityComponents/components/field/DateField.html.twig')]
class DateField extends AbstractEditableField
{
    use Traits\PropertyInfoTrait;

    /**
     * The date value as string for HTML input binding.
     *
     * Format depends on Doctrine column type:
     * - date: 'Y-m-d'
     * - datetime: 'Y-m-d\TH:i'
     * - time: 'H:i'
     */
    #[LiveProp(writable: true)]
    public ?string $dateValue = null;

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);

        if (!$this->editMode) {
            return;
        }

        $this->dateValue = $this->formatValueAsString($this->readValue());
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormFieldConfig(): array
    {
        $type = $this->getDateType();

        return [
            'type'     => $type === 'datetime' ? 'datetime-local' : $type,
            'required' => $this->isRequired(),
        ];
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();
        $this->dateValue = $this->formatValueAsString($this->readValue());
    }

    // ── Template method ────────────────────────────────────────────────────────

    protected function persistEdit(): void
    {
        if ($this->dateValue === null || $this->dateValue === '') {
            $this->writeValue(null);
        } else {
            $this->writeValue($this->parseDateTime($this->dateValue));
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function formatValueAsString(mixed $value): ?string
    {
        if (!$value instanceof DateTimeInterface) {
            return null;
        }

        return match ($this->getDateType()) {
            'date'  => $value->format('Y-m-d'),
            'time'  => $value->format('H:i'),
            default => $value->format('Y-m-d\TH:i'),
        };
    }

    private function parseDateTime(string $dateString): DateTimeInterface
    {
        $type = $this->getDateType();

        $format = match ($type) {
            'date'  => 'Y-m-d',
            'time'  => 'H:i',
            default => 'Y-m-d\TH:i',
        };

        if ($type === 'time') {
            $dateString = date('Y-m-d') . 'T' . $dateString;
            $format     = 'Y-m-d\TH:i';
        }

        $useImmutable = $this->shouldUseImmutable();

        try {
            $dateTime = $useImmutable
                ? DateTimeImmutable::createFromFormat($format, $dateString)
                : \DateTime::createFromFormat($format, $dateString);

            if ($dateTime === false) {
                throw new \RuntimeException('Invalid date format: ' . $dateString);
            }

            return $dateTime;
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid date format: ' . $dateString, 0, $e);
        }
    }

    private function shouldUseImmutable(): bool
    {
        $doctrineType = $this->entityManager
            ->getClassMetadata($this->entityClass)
            ->getTypeOfField($this->property);

        return str_ends_with($doctrineType ?? '', '_immutable');
    }

    private function getDateType(): string
    {
        $doctrineType = $this->entityManager
            ->getClassMetadata($this->entityClass)
            ->getTypeOfField($this->property);

        return match ($doctrineType) {
            'date', 'date_immutable'  => 'date',
            'time', 'time_immutable'  => 'time',
            default                   => 'datetime',
        };
    }
}
