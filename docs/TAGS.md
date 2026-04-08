# TagManager

Add colored, categorized tags to any entity in three steps.

## Quick Start

### 1. Tell Doctrine which class implements `TagInterface`

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        resolve_target_entities:
            Kachnitel\EntityComponentsBundle\Interface\TagInterface: App\Entity\Tag
```

### 2. Create a Tag entity

```php
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;

#[ORM\Entity]
class Tag implements TagInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $value = '';

    public function getId(): ?int { return $this->id; }
    public function getValue(): ?string { return $this->value; }
    public function getDisplayName(): ?string { return $this->value; }
    public function getCategory(): ?string { return null; }
    public function getCategoryColor(): string { return 'cccccc'; }
}
```

### 3. Use the trait on any entity

The `TaggableTrait` includes the full Doctrine mapping — no `#[ORM\ManyToMany]`
declaration needed in your entity:

```php
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;

#[ORM\Entity]
class Product implements TaggableInterface
{
    use TaggableTrait;

    public function __construct() { $this->initializeTags(); }
}
```

### 4. Drop the component into any template

```twig
<twig:K:Entity:TagManager
    :entity="product"
    tagClass="App\\Entity\\Tag"
/>
```

That's it. Users can browse all tags, add and remove them, and save changes in-place.

---

## What's Next?

<details>
<summary><strong>Join table naming</strong></summary>

The bundle automatically generates join table names from the owning entity and
your concrete tag class, e.g. `Product` + `Tag` → `product_tag`,
`UploadedFile` + `Tag` → `uploaded_file_tag`.

This works correctly even when your concrete class name differs from the
interface name (e.g. `UploadedFile implements AttachmentInterface` produces
`product_uploaded_file`, not `product_attachment_interface`). No extra
configuration is required beyond the `resolve_target_entities` entry above.

If you need to override the table name for a specific entity — for example,
to match a legacy schema — redeclare the property in your entity:

```php
class Product implements TaggableInterface
{
    use TaggableTrait;

    #[ORM\ManyToMany(targetEntity: TagInterface::class)]
    #[ORM\JoinTable(name: 'product_tags')]
    private Collection $tags;
}
```

</details>

<details>
<summary><strong>Read-only display</strong></summary>

Pass `readOnly` to show badges without the add/remove controls — useful in list views or for users without edit permissions:

```twig
<twig:K:Entity:TagManager
    :entity="product"
    tagClass="App\\Entity\\Tag"
    :readOnly="true"
/>
```

</details>

<details>
<summary><strong>Tag categories and colors</strong></summary>

Tags are grouped and colored by category. Return a category string and a hex color (without `#`) from your Tag entity:

```php
class Tag implements TagInterface
{
    #[ORM\Column(nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private string $color = 'cccccc'; // stored without #

    public function getCategory(): ?string { return $this->category; }
    public function getCategoryColor(): string { return $this->color; }
}
```

Tags with the same category string appear together. The text color is automatically flipped to black or white depending on background luminance.

</details>

<details>
<summary><strong>Custom display name</strong></summary>

`getDisplayName()` is shown in badges and the picker. It falls back to `getValue()` if `null` is returned, so you only need to override it when the display name differs from the stored value:

```php
public function getDisplayName(): ?string
{
    return $this->label ?? $this->value;
}
```

</details>

<details>
<summary><strong>TaggableTrait reference</strong></summary>

The trait provides `getTags()`, `addTag()`, and `removeTag()`, as well as the
`#[ORM\ManyToMany]` mapping targeting `TagInterface`. You must:

1. Add `use TaggableTrait;` to your entity
2. Call `$this->initializeTags()` in your entity constructor
3. Configure `resolve_target_entities` in `doctrine.yaml` (see Quick Start)

The join table name is derived from your entity and concrete tag class names
and is normalised automatically by the bundle.

</details>

<details>
<summary><strong>Tag interface reference</strong></summary>

| Method | Return | Required | Description |
|---|---|---|---|
| `getId()` | `?int` | ✅ | Primary key |
| `getValue()` | `?string` | ✅ | Stored tag identifier |
| `getDisplayName()` | `?string` | ✅ | Label shown in UI (falls back to value) |
| `getCategory()` | `?string` | ✅ | Optional grouping key |
| `getCategoryColor()` | `string` | ✅ | Hex color without `#`, e.g. `'3498db'` |

</details>
