# Inline-Edit Field Components

Click-to-edit any entity property directly in a template. Each field renders a read-only display with a small ✎ trigger; clicking it swaps in the appropriate input, validates on save, and flushes to the database.

## Quick Start

```twig
<twig:K:Entity:Field:String :entity="user"    property="name" />
<twig:K:Entity:Field:Int    :entity="product" property="stock" />
<twig:K:Entity:Field:Bool   :entity="user"    property="active" />
```

Any property that has a setter is editable by default. No extra configuration needed.

---

## What's Next?

<details>
<summary><strong>All available field types</strong></summary>

| Component tag | Property types | Notes |
|---|---|---|
| `K:Entity:Field:String` | `string`, `text` | Single-line text input |
| `K:Entity:Field:Int` | `int`, `integer` | Number input, `step=1` |
| `K:Entity:Field:Float` | `float`, `decimal` | Number input, `step=any` |
| `K:Entity:Field:Bool` | `bool`, `boolean` | Checkbox |
| `K:Entity:Field:Date` | `date`, `datetime`, `time` (+ `_immutable` variants) | HTML `date` / `datetime-local` / `time` input |
| `K:Entity:Field:Enum` | PHP backed enums | `<select>` with all cases |
| `K:Entity:Field:Relationship` | `ManyToOne`, `OneToOne` | Live search, save/cancel |
| `K:Entity:Field:Collection` | `ManyToMany`, `OneToMany` | Multi-select live search, save/cancel |

</details>

<details>
<summary><strong>Date / datetime fields</strong></summary>

The field auto-detects the Doctrine column type and renders the right HTML input:

```twig
<twig:K:Entity:Field:Date :entity="event" property="startsAt" />   {# datetime-local #}
<twig:K:Entity:Field:Date :entity="user"  property="birthDate" />  {# date only #}
<twig:K:Entity:Field:Date :entity="slot"  property="openAt" />     {# time only #}
```

Immutable variants (`datetime_immutable`, `date_immutable`, `time_immutable`) are handled automatically — the component creates a `DateTimeImmutable` on save.

</details>

<details>
<summary><strong>Enum fields</strong></summary>

Point at any property backed by a PHP enum:

```twig
<twig:K:Entity:Field:Enum :entity="order" property="status" />
```

By default, case names are humanized (`PENDING_APPROVAL` → `Pending Approval`). Add a `label()` or `getLabel()` method to the enum for custom labels:

```php
enum OrderStatus: string
{
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
        };
    }
}
```

</details>

<details>
<summary><strong>Relationship field (ManyToOne / OneToOne)</strong></summary>

For large entity sets where a dropdown would be unwieldy — type to search, select, save or cancel:

```twig
<twig:K:Entity:Field:Relationship :entity="product" property="category" />
```

The search field auto-detects `name`, `label`, or `title` on the related entity. Add `__toString()` for the cleanest label.

</details>

<details>
<summary><strong>Collection field (ManyToMany / OneToMany)</strong></summary>

Multi-select with live search for collections:

```twig
<twig:K:Entity:Field:Collection :entity="post" property="tags" />
```

The component resolves adder/remover method names via Symfony's English inflector (`$tags` → `addTag()` / `removeTag()`). The inflector handles irregular plurals; add standard `addX()` / `removeX()` pairs to your entity.

</details>

<details>
<summary><strong>Validation</strong></summary>

Symfony Validator constraints on the entity are checked before flushing. If validation fails, an inline error message appears and the database is not touched:

```php
#[Assert\NotBlank]
#[Assert\Length(max: 100)]
private ?string $name = null;
```

No extra configuration needed — the field component picks up any constraint already on the property.

</details>

<details>
<summary><strong>Controlling who can edit</strong></summary>

By default any property with a setter is editable. Override `EditabilityResolverInterface` to add role checks, entity-state rules, or any other logic:

```php
// src/Field/MyEditabilityResolver.php
use Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface;

class MyEditabilityResolver implements EditabilityResolverInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    public function canEdit(object $entity, string $property): bool
    {
        if (!$this->security->isGranted('ROLE_EDITOR')) {
            return false;
        }
        return $this->propertyAccessor->isWritable($entity, $property);
    }
}
```

```yaml
# config/services.yaml
Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface:
    alias: App\Field\MyEditabilityResolver
```

`canEdit()` is called on every re-render, so keep it fast. It is also called as a security guard inside `save()` before any entity mutation, regardless of what the template shows.

</details>

<details>
<summary><strong>Customising the value display</strong></summary>

The read-only cell is rendered by `_display.html.twig`. Override it in your app to change how values appear without touching the edit logic:

```twig
{# templates/bundles/KachnitelEntityComponents/components/field/_display.html.twig #}
{% if value is null %}
    <em class="text-muted">not set</em>
{% elseif value is instanceof('\DateTimeInterface') %}
    {{ value|date('d/m/Y H:i') }}
{% else %}
    {{ value }}
{% endif %}
```

The template receives a single `value` variable containing the current property value.

The bundle registers an `is object` Twig test you can use to safely guard against non-stringable objects before echoing `{{ value }}`:

```twig
{% if value is object %}
    {{ value.name ?? value.id }}
{% else %}
    {{ value }}
{% endif %}
```

This is what the default `_display.html.twig` uses internally to dispatch between entity objects, enums, scalars, and null without risking a "could not be converted to string" error.

</details>

<details>
<summary><strong>Field lifecycle</strong></summary>

Every field component follows the same lifecycle:

1. **Display mode** — renders `value` via `_display.html.twig` with a ✎ trigger button (hidden when `canEdit()` returns false)
2. **activateEditing()** — clears any stale error/success state, sets `editMode = true`
3. **Edit mode** — renders the appropriate input bound to `currentValue` (or equivalent)
4. **save()** — guard → write → validate → flush:
   - `canEdit()` checked first (throws if denied)
   - `persistEdit()` writes the new value to the entity
   - Symfony Validator runs `validateProperty()`
   - If invalid: `errorMessage` is set, entity is refreshed, no flush
   - If valid: flush, `editMode = false`, `saveSuccess = true`
5. **cancelEdit()** — refreshes entity from DB, resets input to persisted value, `editMode = false`

A ✓ indicator is shown briefly in display mode after a successful save (`saveSuccess = true`), then disappears on the next `activateEditing()`.

</details>

<details>
<summary><strong>Label auto-generation</strong></summary>

The label shown on the edit input is derived from the property name by splitting camelCase:

| Property | Label |
|---|---|
| `name` | `Name` |
| `createdAt` | `Created At` |
| `firstName` | `First Name` |

Override the `aria-label` attribute on the input if you need a custom label:

```twig
<twig:K:Entity:Field:String :entity="user" property="name"
    aria-label="Full name" />
```

</details>
