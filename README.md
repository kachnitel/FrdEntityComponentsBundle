# Kachnitel Entity Components Bundle

<!-- BADGES -->
![Tests](<https://img.shields.io/badge/tests-301%20passed-red>)
![Coverage](<https://img.shields.io/badge/coverage-51%25-red>)
![Assertions](<https://img.shields.io/badge/assertions-508-blue>)
![PHPStan](<https://img.shields.io/badge/PHPStan-6-brightgreen>)
![PHP](<https://img.shields.io/badge/PHP-&gt;=8.2-777BB4?logo=php&logoColor=white>)
![Symfony](<https://img.shields.io/badge/Symfony-^6.4|^7.0|^8.0-000000?logo=symfony&logoColor=white>)
<!-- BADGES -->

Reusable Symfony Live Components for entity management: tags, file attachments, comments, relationship dropdowns, and a full set of **inline-edit field components** that work with any Doctrine entity.

## Quick Start

### 1. Install

```bash
composer require kachnitel/entity-components-bundle
```

### 2. Use any component

```twig
{{# Inline-edit a text field — any entity property with a setter #}}
<twig:K:Entity:Field:String :entity="user" property="name" />

{{# Tag management #}}
<twig:K:Entity:TagManager :entity="product" tagClass="App\\Entity\\Tag" />

{{# File attachments #}}
<twig:K:Entity:AttachmentManager :entity="product" attachmentClass="App\\Entity\\Attachment" />

{{# Comments #}}
<twig:K:Entity:CommentsManager :entity="article" commentClass="App\\Entity\\Comment" />

{{# Relationship / enum dropdown #}}
<twig:K:Entity:SelectRelationship :entity="order" property="status" />
```

---

## What's Next?

<details>
<summary><strong>Inline-edit any field</strong></summary>

**Level 1:** Drop in a field component — click ✎ to edit, save, or cancel:
```twig
<twig:K:Entity:Field:String  :entity="user"    property="name" />
<twig:K:Entity:Field:Int     :entity="product" property="stock" />
<twig:K:Entity:Field:Bool    :entity="user"    property="active" />
<twig:K:Entity:Field:Date    :entity="event"   property="startsAt" />
<twig:K:Entity:Field:Enum    :entity="order"   property="status" />
```

**Level 2:** Association fields with live search:
```twig
<twig:K:Entity:Field:Relationship :entity="product"  property="category" />
<twig:K:Entity:Field:Collection   :entity="post"     property="tags" />
```

**Level 3:** Add Symfony Validator constraints to the entity — validation runs automatically before flushing:
```php
#[Assert\NotBlank]
#[Assert\Length(max: 100)]
private ?string $name = null;
```

**Level 4:** Control who can edit by overriding `EditabilityResolverInterface`:
```yaml
Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface:
    alias: App\Field\MyEditabilityResolver
```

**Details:** [Inline-Edit Guide](docs/INLINE_EDIT.md)

</details>

<details>
<summary><strong>Tag management</strong></summary>

**Level 1:** Implement `TagInterface` and `TaggableInterface`, drop in the component:
```twig
<twig:K:Entity:TagManager :entity="product" tagClass="App\\Entity\\Tag" />
```

**Level 2:** Read-only badge display:
```twig
<twig:K:Entity:TagManager :entity="product" tagClass="App\\Entity\\Tag" :readOnly="true" />
```

**Level 3:** Colored categories — return a hex color from `getCategoryColor()` on your Tag entity. Text color is flipped automatically for contrast.

**Details:** [Tags Guide](docs/TAGS.md)

</details>

<details>
<summary><strong>File attachments</strong></summary>

**Level 1:** Register a `FileHandlerInterface` service, implement `AttachableInterface`, drop in the component:
```twig
<twig:K:Entity:AttachmentManager :entity="product" attachmentClass="App\\Entity\\Attachment" />
```

**Level 2:** Read-only display, custom collection property:
```twig
:options="new AttachmentManagerOptions(readOnly: true, property: 'media')"
```

**Level 3:** Per-attachment tagging:
```twig
:options="new AttachmentManagerOptions(tagClass: 'App\\Entity\\Tag')"
```

**Details:** [Attachments Guide](docs/ATTACHMENTS.md)

</details>

<details>
<summary><strong>Comments</strong></summary>

**Level 1:** Implement `CommentInterface` and `CommentableInterface`, drop in the component:
```twig
<twig:K:Entity:CommentsManager :entity="article" commentClass="App\\Entity\\Comment" />
```

**Level 2:** Read-only display, custom collection property:
```twig
:options="new CommentsManagerOptions(readOnly: true, property: 'notes')"
```

**Level 3:** Limit text length — add a `MAX_TEXT_LENGTH` constant to your Comment entity and the textarea `maxlength` is set automatically.

**Details:** [Comments Guide](docs/COMMENTS.md)

</details>

<details>
<summary><strong>Relationship / enum dropdown</strong></summary>

**Level 1:** Works out of the box for any entity relation or backed enum:
```twig
<twig:K:Entity:SelectRelationship :entity="order" property="region" />
```

**Level 2:** Access control, placeholder, label:
```twig
:options="new SelectRelationshipOptions(
    role: 'ROLE_EDITOR',
    placeholder: '— Select Region —',
)"
```

**Level 3:** Filter records or use a custom repository method:
```twig
:options="new SelectRelationshipOptions(filter: { active: true })"
:options="new SelectRelationshipOptions(repositoryMethod: 'findActive')"
```

**Details:** [SelectRelationship Guide](docs/SELECT_RELATIONSHIP.md)

</details>

---

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

## Documentation

| Guide | Description |
|---|---|
| [Inline-Edit Fields](docs/INLINE_EDIT.md) | All field types, validation, editability control, display override |
| [Tags](docs/TAGS.md) | TagManager setup, categories, colors |
| [Attachments](docs/ATTACHMENTS.md) | AttachmentManager setup, FileHandlerInterface, template blocks |
| [Comments](docs/COMMENTS.md) | CommentsManager setup, author attribution, text limits |
| [SelectRelationship](docs/SELECT_RELATIONSHIP.md) | Dropdown for relations and enums, access control, filtering |

## Requirements

- PHP 8.2+
- Symfony 6.4, 7.x, or 8.x
- Doctrine ORM
- Symfony UX Live Component

## License

MIT
