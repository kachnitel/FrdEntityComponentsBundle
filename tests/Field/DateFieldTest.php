<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Kachnitel\EntityComponentsBundle\Components\Field\DateField;
use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestDateEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(DateField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-date')]
class DateFieldTest extends FieldTestCase
{
    private function createEntity(): FieldTestDateEntity
    {
        $entity = new FieldTestDateEntity();
        $entity->setCreatedAt(new DateTime('2024-06-01 12:30:00'));
        $entity->setUpdatedAt(new DateTimeImmutable('2024-06-15 14:00:00'));
        $entity->setBirthDate(new DateTime('2000-01-15'));
        $entity->setExpiresOn(new DateTimeImmutable('2025-12-31'));
        $entity->setMeetingTime(new DateTime('1970-01-01 14:30:00'));
        $entity->setLoggedAt(new DateTimeImmutable('1970-01-01 09:15:00'));
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): DateField
    {
        return $this->getFieldComponent(DateField::class);
    }

    // ── mount(): dateValue initialization ─────────────────────────────────────

    public function testMountConvertsDatetimeToDatetimeLocalString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $this->assertSame($entity->getCreatedAt()?->format('Y-m-d\TH:i'), $component->dateValue);
    }

    public function testMountConvertsDatetimeImmutableToDatetimeLocalString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'updatedAt');

        $this->assertSame($entity->getUpdatedAt()?->format('Y-m-d\TH:i'), $component->dateValue);
    }

    public function testMountConvertsDateToDateString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'birthDate');

        $this->assertSame($entity->getBirthDate()?->format('Y-m-d'), $component->dateValue);
    }

    public function testMountConvertsTimeToTimeString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'meetingTime');

        $this->assertSame($entity->getMeetingTime()?->format('H:i'), $component->dateValue);
    }

    public function testMountSetsNullWhenNotInEditMode(): void
    {
        $component = $this->getComponent();
        $component->editMode = false;
        $component->mount($this->createEntity(), 'createdAt');

        $this->assertNull($component->dateValue);
    }

    public function testMountSetsNullWhenFieldIsNull(): void
    {
        $entity = new FieldTestDateEntity();
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $this->assertNull($component->dateValue);
    }

    // ── canEdit() ─────────────────────────────────────────────────────────────

    public function testCanEditReturnsTrueForWritableProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'createdAt');

        $this->assertTrue($component->canEdit());
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSaveConvertsDatetimeStringToDateTime(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2025-03-15T14:30';
        $component->save();

        $result = $entity->getCreatedAt();
        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('2025-03-15', $result->format('Y-m-d'));
        $this->assertSame('14:30', $result->format('H:i'));
    }

    public function testSaveCreatesPlainDateTimeForMutableColumn(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2025-06-01T09:00';
        $component->save();

        $this->assertInstanceOf(DateTime::class, $entity->getCreatedAt());
        $this->assertNotInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
    }

    public function testSaveCreatesDateTimeImmutableForImmutableColumn(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'updatedAt');

        $component->dateValue = '2025-06-01T09:00';
        $component->save();

        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getUpdatedAt());
    }

    public function testSaveHandlesDateOnlyString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'birthDate');

        $component->dateValue = '2025-03-15';
        $component->save();

        $this->assertSame('2025-03-15', $entity->getBirthDate()?->format('Y-m-d'));
    }

    public function testSaveHandlesDateImmutableString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'expiresOn');

        $component->dateValue = '2026-01-01';
        $component->save();

        $result = $entity->getExpiresOn();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2026-01-01', $result->format('Y-m-d'));
    }

    public function testSaveHandlesTimeOnlyString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'meetingTime');

        $component->dateValue = '14:30';
        $component->save();

        $this->assertSame('14:30', $entity->getMeetingTime()?->format('H:i'));
    }

    public function testSaveHandlesTimeImmutableString(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'loggedAt');

        $component->dateValue = '09:00';
        $component->save();

        $result = $entity->getLoggedAt();
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('09:00', $result->format('H:i'));
    }

    public function testSaveHandlesNullDateValue(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = null;
        $component->save();

        $this->assertNull($entity->getCreatedAt());
    }

    public function testSaveHandlesEmptyStringAsNull(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '';
        $component->save();

        $this->assertNull($entity->getCreatedAt());
    }

    public function testSaveExitsEditModeAndSetsSuccessFlag(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2025-07-04T08:00';
        $component->save();

        $this->assertFalse($component->editMode);
        $this->assertTrue($component->saveSuccess);
    }

    public function testSaveFlushesToDatabase(): void
    {
        $entity = $this->createEntity();
        $id     = $entity->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2025-07-04T08:00';
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestDateEntity::class, $id);
        $this->assertSame('2025-07-04', $reloaded?->getCreatedAt()?->format('Y-m-d'));
    }

    public function testInvalidDateStringThrowsRuntimeException(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = 'not-a-date';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid date format');

        $component->save();
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditResetsDatetimeValueToPersistedState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2099-12-31T23:59';
        $component->cancelEdit();

        $this->assertSame(
            $entity->getCreatedAt()?->format('Y-m-d\TH:i'),
            $component->dateValue,
        );
    }

    public function testCancelEditSetsNullWhenPersistedValueIsNull(): void
    {
        $entity = new FieldTestDateEntity();
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'createdAt');

        $component->dateValue = '2025-01-01T00:00';
        $component->cancelEdit();

        $this->assertNull($component->dateValue);
    }

    public function testCancelEditExitsEditModeAndClearsError(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'old error';
        $component->mount($entity, 'createdAt');

        $component->cancelEdit();

        $this->assertFalse($component->editMode);
        $this->assertSame('', $component->errorMessage);
    }

    // ── getFormFieldConfig() ──────────────────────────────────────────────────

    public function testGetFormFieldConfigReturnsDatetimeLocalForDatetime(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'createdAt');

        $this->assertSame('datetime-local', $component->getFormFieldConfig()['type']);
    }

    public function testGetFormFieldConfigReturnsDateForDateColumn(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'birthDate');

        $this->assertSame('date', $component->getFormFieldConfig()['type']);
    }

    public function testGetFormFieldConfigReturnsTimeForTimeColumn(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'meetingTime');

        $this->assertSame('time', $component->getFormFieldConfig()['type']);
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'createdAt');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
