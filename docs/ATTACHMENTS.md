# AttachmentManager

Upload, display, and delete file attachments on any entity.

## Quick Start

### 1. Create an Attachment entity

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
    public function getPath(): ?string { return $this->url; } // public URL
}
```

### 2. Implement AttachableInterface on your entity

```php
use Kachnitel\EntityComponentsBundle\Interface\AttachableInterface;
use Kachnitel\EntityComponentsBundle\Trait\AttachableTrait;

#[ORM\Entity]
class Product implements AttachableInterface
{
    use AttachableTrait;

    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $attachments;

    public function __construct() { $this->initializeAttachments(); }
}
```

### 3. Register a FileHandlerInterface service

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

Symfony's autowiring automatically aliases `FileHandlerInterface` to the single implementation — no explicit `alias:` needed. If you ever have more than one implementation, mark the preferred one with `#[AsAlias]`:

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(FileHandlerInterface::class)]
class LocalFileHandler implements FileHandlerInterface { ... }
```

### 4. Drop the component into any template

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\Attachment"
/>
```

Users can browse existing attachments, upload new files, and delete files in-place.

---

## What's Next?

<details>
<summary><strong>Read-only display</strong></summary>

Hide the upload form and delete buttons — useful for non-admin users:

```twig
<twig:K:Entity:AttachmentManager
    :entity="product"
    attachmentClass="App\\Entity\\Attachment"
    :options="new AttachmentManagerOptions(readOnly: true)"
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
    :options="new AttachmentManagerOptions(tagClass: 'App\\Entity\\Tag')"
/>
```

</details>

<details>
<summary><strong>Custom collection property</strong></summary>

If your entity stores attachments under a different property name (e.g. `media`), pass `property`:

```twig
<twig:K:Entity:AttachmentManager
    :entity="article"
    attachmentClass="App\\Entity\\Media"
    :options="new AttachmentManagerOptions(property: 'media')"
/>
```

The component derives the adder/remover method names from this property (`addMedium` / `removeMedium`) using Symfony's English inflector.

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
    {# Your custom preview markup here #}
    <img src="{{ attachment.path }}" class="rounded shadow-sm" style="width:100%">
{% endblock %}
```

Available blocks: `errors`, `attachment_list`, `attachment_item`, `attachment_preview`, `tag_manager`, `attachment_actions`, `no_attachments`, `upload_form`.

</details>