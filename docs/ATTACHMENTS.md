# AttachmentManager

Upload, display, and delete file attachments on any entity.

## Quick Start

### 1. Tell Doctrine which class implements `AttachmentInterface`

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        resolve_target_entities:
            Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface: App\Entity\Attachment
```

### 2. Create an Attachment entity

```php
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;

#[ORM\Entity]
class Attachment implements AttachmentInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $url = '';

    #[ORM\Column]
    private string $mimeType = '';

    public function getId(): ?int { return $this->id; }
    public function getUrl(): ?string { return $this->url; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function getPath(): ?string { return $this->url; }
}
```

### 3. Use the trait on any entity

The `AttachableTrait` includes the full Doctrine mapping — no `#[ORM\ManyToMany]`
declaration needed in your entity:

```php
use Kachnitel\EntityComponentsBundle\Interface\AttachableInterface;
use Kachnitel\EntityComponentsBundle\Trait\AttachableTrait;

#[ORM\Entity]
class Product implements AttachableInterface
{
    use AttachableTrait;

    public function __construct() { $this->initializeAttachments(); }
}
```

### 4. Register a FileHandlerInterface service

```php
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LocalFileHandler implements FileHandlerInterface
{
    public function __construct(private string $uploadDir) {}

    public function handle(UploadedFile $file): AttachmentInterface
    {
        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($this->uploadDir, $filename);

        $attachment = new Attachment();
        $attachment->setUrl('/uploads/' . $filename);
        $attachment->setMimeType($file->getMimeType() ?? 'application/octet-stream');

        return $attachment;
    }

    public function deleteFile(AttachmentInterface $attachment): void
    {
        // remove from disk / S3 / etc.
    }
}
```

```yaml
# config/services.yaml
App\Service\LocalFileHandler:
    arguments:
        $uploadDir: '%kernel.project_dir%/public/uploads'
```

Symfony's autowiring automatically aliases `FileHandlerInterface` to the single implementation. If you ever have more than one, mark the preferred one with `#[AsAlias]`:

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(FileHandlerInterface::class)]
class LocalFileHandler implements FileHandlerInterface { ... }
```

### 5. Drop the component into any template

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\Attachment"
/>
```

---

## What's Next?

<details>
<summary><strong>Join table naming</strong></summary>

The bundle automatically generates join table names from the owning entity and
your concrete attachment class, e.g. `Product` + `Attachment` → `product_attachment`,
`PurchaseOrder` + `Attachment` → `purchase_order_attachment`.

This works correctly even when your concrete class name differs from the interface
name (e.g. `UploadedFile implements AttachmentInterface` produces `product_uploaded_file`,
not `product_attachment_interface`). No extra configuration is required beyond the
`resolve_target_entities` entry above.

If you need to override the table name for a specific entity — for example, to match
a legacy schema — redeclare the property in your entity:

```php
class Product implements AttachableInterface
{
    use AttachableTrait;

    #[ORM\ManyToMany(targetEntity: AttachmentInterface::class)]
    #[ORM\JoinTable(name: 'product_files')]
    private Collection $attachments;
}
```

</details>

<details>
<summary><strong>Read-only display</strong></summary>

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\Attachment"
    :config="{ readOnly: true }"
/>
```

</details>

<details>
<summary><strong>Per-attachment tagging</strong></summary>

Pass a `tagClass` to render a `TagManager` beneath each attachment thumbnail.
Your `Tag` class must implement `TagInterface` and your `Attachment` entity must implement `TaggableInterface`:

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\Attachment"
    :config="{ tagClass: 'App\\Entity\\Tag' }"
/>
```

</details>

<details>
<summary><strong>Custom collection property</strong></summary>

If your entity stores attachments under a different property name (e.g. `media`):

```twig
<twig:K:Entity:AttachmentManager
    :entity="article"
    attachmentClass="App\\Entity\\Media"
    :config="{ property: 'media' }"
/>
```

The component derives adder/remover method names from this property using Symfony's English inflector.

</details>

<details>
<summary><strong>AttachableTrait reference</strong></summary>

The trait provides `getAttachments()`, `addAttachment()`, and `removeAttachment()`,
as well as the `#[ORM\ManyToMany]` mapping targeting `AttachmentInterface`. You must:

1. Add `use AttachableTrait;` to your entity
2. Call `$this->initializeAttachments()` in your entity constructor
3. Configure `resolve_target_entities` in `doctrine.yaml` (see Quick Start)

The join table name is derived from your entity and concrete attachment class
names and is normalised automatically by the bundle.

</details>

<details>
<summary><strong>AttachmentManagerOptions reference</strong></summary>

| Option | Type | Default | Description |
|---|---|---|---|
| `readOnly` | `bool` | `false` | Hide upload form and delete buttons |
| `property` | `string` | `'attachments'` | Collection property name on the entity |
| `tagClass` | `string\|null` | `null` | FQCN of a `TagInterface` entity — renders a TagManager per attachment |

</details>

<details>
<summary><strong>AttachmentInterface reference</strong></summary>

| Method | Return | Description |
|---|---|---|
| `getId()` | `?int` | Primary key |
| `getUrl()` | `?string` | File URL — shown as link text and alt text |
| `getMimeType()` | `?string` | Used to choose image preview vs file icon |
| `getPath()` | `?string` | Public URL — used as `src` / `href` |

</details>

<details>
<summary><strong>Customising the template</strong></summary>

Override any block in your app without copying the whole template:

```twig
{# templates/bundles/KachnitelEntityComponents/components/AttachmentManager.html.twig #}
{% extends '@!KachnitelEntityComponents/components/AttachmentManager.html.twig' %}

{% block attachment_preview %}
    <img src="{{ attachment.path }}" class="rounded shadow-sm" style="width:100%">
{% endblock %}
```

Available blocks: `errors`, `attachment_list`, `attachment_item`, `attachment_preview`, `tag_manager`, `attachment_actions`, `no_attachments`, `upload_form`.

</details>
