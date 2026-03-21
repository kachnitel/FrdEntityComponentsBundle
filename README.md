# Kachnitel Entity Components Bundle

<!-- BADGES -->
![Tests](<https://img.shields.io/badge/tests-261%20passed-red>)
![Coverage](<https://img.shields.io/badge/coverage-51%25-red>)
![Assertions](<https://img.shields.io/badge/assertions-443-blue>)
![PHPStan](<https://img.shields.io/badge/PHPStan-6-brightgreen>)
![PHP](<https://img.shields.io/badge/PHP-&gt;=8.2-777BB4?logo=php&logoColor=white>)
![Symfony](<https://img.shields.io/badge/Symfony-^6.4|^7.0|^8.0-000000?logo=symfony&logoColor=white>)
<!-- BADGES -->

Reusable Symfony Live Components for entity management. Provides tag management, attachment management, comments, relationship selectors, and a full set of **inline-edit field components** that work with any Doctrine entity.

## Components at a glance

| Component | Tag | Description |
|---|---|---|
| `TagManager` | `K:Entity:TagManager` | Colored tag badges with category grouping |
| `AttachmentManager` | `K:Entity:AttachmentManager` | File upload and attachment list |
| `CommentsManager` | `K:Entity:CommentsManager` | Threaded comments with delete confirmation |
| `SelectRelationship` | `K:Entity:SelectRelationship` | Eager dropdown for small option sets and enums |
| `StringField` | `K:Entity:Field:String` | Inline text edit |
| `IntField` | `K:Entity:Field:Int` | Inline integer edit |
| `FloatField` | `K:Entity:Field:Float` | Inline decimal edit |
| `BoolField` | `K:Entity:Field:Bool` | Inline checkbox toggle |
| `DateField` | `K:Entity:Field:Date` | Inline date / datetime / time edit |
| `EnumField` | `K:Entity:Field:Enum` | Inline dropdown for PHP backed enums |
| `RelationshipField` | `K:Entity:Field:Relationship` | Live-search inline editor for ManyToOne / OneToOne |
| `CollectionField` | `K:Entity:Field:Collection` | Live-search inline editor for ManyToMany / OneToMany |

## Installation

```bash
composer require kachnitel/entity-components-bundle
```

---

## Inline-Edit Field Components

All field components share the same lifecycle:

- **Display mode** — renders the current value with an ✎ edit trigger (when permitted)
- **Edit mode** — renders an input / select, with Save and Cancel buttons
- **Save** — validates via Symfony Validator before flushing; shows inline error on failure
- **Cancel** — discards unsaved input and refreshes from the database

### Basic usage

```twig
{# Any property with a setter becomes inline-editable #}
<twig:K:Entity:Field:String  :entity="user"    property="name" />
<twig:K:Entity:Field:Int     :entity="product" property="stock" />
<twig:K:Entity:Field:Float   :entity="product" property="price" />
<twig:K:Entity:Field:Bool    :entity="user"    property="active" />
<twig:K:Entity:Field:Date    :entity="event"   property="startsAt" />
<twig:K:Entity:Field:Enum    :entity="order"   property="status" />

{# Association fields — live search for large datasets #}
<twig:K:Entity:Field:Relationship :entity="product"  property="category" />
<twig:K:Entity:Field:Collection   :entity="post"     property="tags" />
```

### Controlling editability

By default all properties with a setter are editable. Override `EditabilityResolverInterface`
to enforce your own policy — role checks, entity state, attribute flags, etc.:

```yaml
# config/services.yaml
Kachnitel\EntityComponentsBundle\Field\EditabilityResolverInterface:
    alias: App\Field\MyEditabilityResolver
```

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

`kachnitel/admin-bundle` registers its own `AdminEditabilityResolver` which reads
`#[AdminColumn(editable: ...)]` and `#[Admin(enableInlineEdit: true)]` attributes
and checks the `ADMIN_EDIT` voter automatically.

### Customising the value display

The read-only cell uses `_display.html.twig`, which renders `{{ value }}` by default.
Override it in your app for richer display:

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

### EnumField — custom labels

If your enum implements `label()` or `getLabel()`, it will be used in the dropdown:

```php
enum OrderStatus: string
{
    case Pending  = 'pending';
    case Shipped  = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting shipment',
            self::Shipped => 'On its way',
        };
    }
}
```

Otherwise the case name is humanized: `PENDING_APPROVAL` → `Pending Approval`.

### RelationshipField & CollectionField — label resolution

The live search labels are resolved in this order:

1. `__toString()` on the target entity
2. First of `name`, `label`, `title` that exists
3. `ClassName #ID` fallback

Add `__toString()` to your related entity for the cleanest labels.

### Choosing between relationship/collection components

| Need | Component |
|---|---|
| Single-value relation, large dataset, save/cancel | `K:Entity:Field:Relationship` |
| Multi-value collection, large dataset, save/cancel | `K:Entity:Field:Collection` |
| Single-value relation or enum, small/static set, persist-on-change | `K:Entity:SelectRelationship` |
| Tagging with colored category badges | `K:Entity:TagManager` |

---

## TagManager

Live Component for managing tags on entities.

**Props:**
- `entity` (TaggableInterface) — the entity to manage tags for
- `tagClass` (string) — FQCN of your Tag entity
- `readOnly` (bool) — disable editing (default: `false`)

```twig
<twig:K:Entity:TagManager
    :entity="product"
    tagClass="App\\Entity\\Tag"
/>
```

---

## AttachmentManager

Live Component for managing file attachments on entities.

**Props:**
- `entity` (AttachableInterface) — the entity to manage attachments for
- `attachmentClass` (string) — FQCN of your Attachment entity
- `readOnly` (bool) — disable file uploads/deletion (default: `false`)
- `property` (string) — property name for the collection (default: `'attachments'`)

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\UploadedFile"
/>
```

Register a `FileHandlerInterface` service in your app:

```php
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;

class LocalStorageHandler implements FileHandlerInterface
{
    public function handle(UploadedFile $file): AttachmentInterface { /* ... */ }
    public function deleteFile(AttachmentInterface $attachment): void { /* ... */ }
}
```

---

## CommentsManager

Live Component for threaded comments with delete confirmation.

**Props:**
- `entity` (CommentableInterface) — the entity to manage comments for
- `commentClass` (string) — FQCN of your Comment entity
- `readOnly` (bool) — disable new comments and deletion (default: `false`)

```twig
<twig:K:Entity:CommentsManager
    :entity="article"
    commentClass="App\\Entity\\Comment"
/>
```

---

## SelectRelationship

Live Component for inline editing of entity relationships and enums via an eager dropdown.
Automatically detects whether the target property is an entity or enum.

**Props:**
- `entity` (object) — the entity containing the property to edit
- `property` (string) — the property name
- `placeholder` (string) — empty option text (default: `'-'`)
- `displayProperty` (string) — property used as option label (default: `'name'`)
- `valueProperty` (string) — property used as option value (default: `'id'`)
- `filter` (array) — simple `findBy()` criteria, e.g. `{ active: true }`
- `repositoryMethod` (string) — custom repository method name
- `repositoryArgs` (array) — arguments for the custom repository method
- `role` (string) — role required to show the editable select
- `viewRole` (string) — role required to show the read-only display
- `disableEmpty` (bool) — disable the empty option (default: `false`)
- `disabled` (bool) — force read-only (default: `false`)

```twig
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="region"
    role="ROLE_ORDER_REGION_EDIT"
    placeholder="— Select Region —"
/>
```

---

## Installation details

### 1. Create a Tag Entity

```php
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;

#[ORM\Entity]
class Tag implements TagInterface
{
    // ... getId(), getValue(), getDisplayName(), getCategory(), getCategoryColor()
}
```

### 2. Make your entity taggable

```php
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;

#[ORM\Entity]
class Product implements TaggableInterface
{
    use TaggableTrait;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    public function __construct() { $this->initializeTags(); }
}
```

---

## Requirements

- PHP 8.2+
- Symfony 6.4, 7.x, or 8.x
- Doctrine ORM
- Symfony UX Live Component

## License

MIT

## Contributing

Contributions welcome! This bundle aims to provide generic, reusable components for Symfony applications.
