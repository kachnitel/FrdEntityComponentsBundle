<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\StringField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Runtime\EscaperRuntime;

/**
 * Tests that StringField.html.twig (and the shared _display.html.twig sub-template)
 * produces the correct HTML for three observable states:
 *
 *   1. Edit mode + validation error  — `is-invalid` class and error text must appear
 *   2. Display mode + save success   — `inline-edit-saved` indicator must appear
 *   3. Display mode, no save         — `inline-edit-saved` must be absent
 *
 * Templates are rendered directly via Twig rather than through the LiveComponent
 * HTTP stack. This mirrors the approach in DisplayTemplateTest and avoids the
 * WebTestCase / browser-kit overhead while still exercising the real template files.
 *
 * Variables are assembled to match exactly what TwigComponent exposes at render time:
 *   - Public LiveProps as standalone template variables (editMode, errorMessage, saveSuccess)
 *   - The component object as `this`  (for this.canEdit, this.currentValue, this.label)
 *   - `value` from readValue()  (consumed by the _display.html.twig sub-include)
 *   - An empty ComponentAttributes instance  (for {{ attributes }})
 */
#[CoversNothing]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-template-render')]
class FieldTemplateRenderTest extends FieldTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createEntity(string $name = 'Template Test'): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setName($name);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    /**
     * Render StringField.html.twig for the given component, replicating the
     * variable set that TwigComponent's renderer would inject at runtime.
     */
    private function renderStringField(StringField $component): string
    {
        /** @var \Twig\Environment $twig */
        $twig = static::getContainer()->get('twig');

        return trim($twig->render(
            '@KachnitelEntityComponents/components/field/StringField.html.twig',
            [
                // Component object — accessed as `this.*` in the template
                'this'         => $component,
                // LiveProps exposed as standalone template variables
                'editMode'     => $component->editMode,
                'errorMessage' => $component->errorMessage,
                'saveSuccess'  => $component->saveSuccess,
                // Value exposed via #[ExposeInTemplate('value')], consumed by _display.html.twig
                'value'        => $component->readValue(),
                // Empty attributes bag required by {{ attributes }} in the template
                'attributes'   => new ComponentAttributes([], $twig->getRuntime(EscaperRuntime::class))
            ]
        ));
    }

    // ── Test 1: error state in edit mode ─────────────────────────────────────

    /**
     * When a save fails validation the component re-renders in edit mode with
     * errorMessage non-empty. The template must add `is-invalid` to the input
     * and render the error text in an .invalid-feedback element.
     */
    public function testEditModeWithErrorMessageRendersIsInvalidClassAndErrorText(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->mount($entity, 'name');
        // Simulate a post-save failure state
        $component->editMode     = true;
        $component->errorMessage = 'This value is too long.';

        $html = $this->renderStringField($component);

        $this->assertStringContainsString('is-invalid', $html, 'Input must carry is-invalid class when errorMessage is set');
        $this->assertStringContainsString('This value is too long.', $html, 'Error text must appear in the rendered output');
    }

    // ── Test 2: save-success indicator in display mode ────────────────────────

    /**
     * After a successful flush the component sets saveSuccess=true and exits
     * edit mode. The display-mode branch of the template must show the ✓ indicator.
     */
    public function testDisplayModeWithSaveSuccessTrueRendersSuccessIndicator(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->mount($entity, 'name');
        $component->editMode    = false;
        $component->saveSuccess = true;

        $html = $this->renderStringField($component);

        $this->assertStringContainsString('inline-edit-saved', $html, 'inline-edit-saved element must appear after a successful save');
        $this->assertStringContainsString('✓', $html, 'Checkmark must be present in the save-success indicator');
    }

    // ── Test 3: no success indicator on initial display ───────────────────────

    /**
     * On first render (saveSuccess defaults to false) the success indicator
     * must be absent so that the display mode looks clean before any edit.
     */
    public function testDisplayModeWithSaveSuccessFalseDoesNotRenderSuccessIndicator(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->mount($entity, 'name');
        // saveSuccess defaults to false — explicitly assert the default path
        $this->assertFalse($component->saveSuccess, 'Precondition: saveSuccess must default to false');

        $html = $this->renderStringField($component);

        $this->assertStringNotContainsString('inline-edit-saved', $html, 'Success indicator must not appear when saveSuccess is false');
    }
}
