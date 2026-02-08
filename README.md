# Kachnitel Entity Components Bundle

<!-- BADGES -->
![Tests](<https://img.shields.io/badge/tests-42%20passed-brightgreen>)
![Coverage](<https://img.shields.io/badge/coverage-27%25-red>)
![Assertions](<https://img.shields.io/badge/assertions-98-blue>)
![PHPStan](<https://img.shields.io/badge/PHPStan-6-brightgreen>)
![PHP](<https://img.shields.io/badge/PHP-&gt;=8.2-777BB4?logo=php&logoColor=white>)
![Symfony](<https://img.shields.io/badge/Symfony-^6.4|^7.0|^8.0-000000?logo=symfony&logoColor=white>)
<!-- BADGES -->

Reusable Symfony Live Components for entity management. Provides tag and attachment management components that work with any Doctrine entity.

## Features

- **TagManager** - Live Component for managing tags on any entity
- **AttachmentManager** - Live Component for file uploads and attachments
- **SelectRelationship** - Live Component for editing entity relationships and enums inline
- **Generic interfaces** - Work with your own Tag/Attachment entities
- **Reusable traits** - Easy implementation for entities
- **Framework-agnostic design** - Minimal dependencies

## Installation

```bash
composer require kachnitel/entity-components-bundle
```

## Quick Start

### 1. Create a Tag Entity

```php
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;

#[ORM\Entity]
class Tag implements TagInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $value;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $category = null;

    // Implement TagInterface methods...
}
```

### 2. Make Your Entity Taggable

```php
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;

#[ORM\Entity]
class Product implements TaggableInterface
{
    use TaggableTrait;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    public function __construct()
    {
        $this->initializeTags();
    }
}
```

### 3. Use the TagManager Component

```twig
<twig:K:Entity:TagManager
    entity="{{ product }}"
    tagClass="App\\Entity\\Tag"
/>
```

## Attachments Quick Start

### 1. Create an Attachment Entity

```php
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;

#[ORM\Entity]
class UploadedFile implements AttachmentInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $url;

    #[ORM\Column(length: 100)]
    private string $mimeType;

    #[ORM\Column(length: 255)]
    private string $path;

    // Implement AttachmentInterface methods...
}
```

### 2. Make Your Entity Attachable

```php
use Kachnitel\EntityComponentsBundle\Interface\AttachableInterface;
use Kachnitel\EntityComponentsBundle\Trait\AttachableTrait;

#[ORM\Entity]
class Product implements AttachableInterface
{
    use AttachableTrait;

    #[ORM\ManyToMany(targetEntity: UploadedFile::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(unique: true)]
    private Collection $attachments;

    public function __construct()
    {
        $this->initializeAttachments();
    }
}
```

### 3. Implement FileHandlerInterface

```php
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;

class LocalStorageHandler implements FileHandlerInterface
{
    public function __construct(
        private string $uploadDirectory,
        private EntityManagerInterface $entityManager
    ) {}

    public function handle(UploadedFile $file): AttachmentInterface
    {
        $fileName = uniqid() . '.' . $file->guessExtension();
        $file->move($this->uploadDirectory, $fileName);

        $uploadedFile = new \App\Entity\UploadedFile();
        $uploadedFile->setUrl($fileName);
        $uploadedFile->setPath('/uploads/' . $fileName);
        $uploadedFile->setMimeType($file->getMimeType());

        $this->entityManager->persist($uploadedFile);

        return $uploadedFile;
    }

    public function deleteFile(AttachmentInterface $attachment): void
    {
        $filePath = $this->uploadDirectory . '/' . $attachment->getUrl();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($attachment);
    }
}
```

### 4. Use the AttachmentManager Component

```twig
<twig:K:Entity:AttachmentManager
    entity="{{ product }}"
    attachmentClass="App\\Entity\\UploadedFile"
/>
```

## Components

### TagManager

Live Component for managing tags on entities.

**Props:**
- `entity` (TaggableInterface) - The entity to manage tags for
- `tagClass` (string) - FQCN of your Tag entity
- `readOnly` (bool) - Disable editing (default: false)

**Events:**
- `toast.show` - Dispatched on save/error with message
- `modal.close` - Dispatched after successful save

**Usage:**
```twig
<twig:K:Entity:TagManager
    entity="{{ product }}"
    tagClass="App\\Entity\\Tag"
/>
```

### AttachmentManager

Live Component for managing file attachments on entities.

**Props:**
- `entity` (AttachableInterface) - The entity to manage attachments for
- `attachmentClass` (string) - FQCN of your Attachment entity
- `readOnly` (bool) - Disable file uploads/deletion (default: false)
- `property` (string) - Property name for attachments (default: 'attachments')

**Events:**
- `toast.show` - Dispatched on upload/delete/error with message

**Usage:**
```twig
<twig:K:Entity:AttachmentManager
    entity="{{ product }}"
    attachmentClass="App\\Entity\\UploadedFile"
/>
```

**Required Service:**
You must implement and register a `FileHandlerInterface` service:

```php
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LocalStorageHandler implements FileHandlerInterface
{
    public function handle(UploadedFile $file): AttachmentInterface
    {
        // Handle file upload, store it, create and return your Attachment entity
    }

    public function deleteFile(AttachmentInterface $attachment): void
    {
        // Delete the file from storage
    }
}
```

### SelectRelationship

Live Component for inline editing of entity relationships and enum properties. Automatically detects whether the target property is an Entity or Enum and loads appropriate options.

**Props:**
- `entity` (object) - The entity containing the property to edit (required)
- `property` (string) - The property name to edit (required)
- `role` (string) - Role required to show the editable select (optional)
- `viewRole` (string) - Role required to show static display when edit role not granted (optional)
- `placeholder` (string) - Placeholder text for empty option (default: '-')
- `displayProperty` (string) - Property to display for options (default: 'name')
- `valueProperty` (string) - Property to use as value (default: 'id')
- `filter` (array) - Simple criteria for findBy() e.g., `{ active: true }` (optional)
- `repositoryMethod` (string) - Custom repository method name e.g., 'findByRoles' (optional)
- `repositoryArgs` (array) - Arguments for custom repository method (optional)
- `disableEmpty` (bool) - Disable selection of empty option (default: false)
- `disabled` (bool) - Force disabled state (default: false)
- `label` (string) - Optional label text

**Usage:**
```twig
{# Basic usage with entity relationship #}
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="region"
    role="ROLE_ORDER_REGION_EDIT"
    placeholder="- Select Region -"
    select:class="form-select"
/>

{# With enum property #}
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="status"
    role="ROLE_ORDER_STATUS_EDIT"
/>

{# With simple filter #}
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="assignedTo"
    :filter="{ active: true }"
    displayProperty="fullName"
/>

{# With custom repository method #}
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="assignedTo"
    repositoryMethod="findByRoles"
    :repositoryArgs="[['ROLE_TERRITORY_MANAGER']]"
/>
```

**Nested Attributes:**
- `select:*` - Attributes for the `<select>` element (e.g., `select:class="compact"`)
- `static:*` - Attributes for the static display `<span>` (e.g., `static:class="text-muted"`)

**Enum Support:**
Enums are automatically detected. If your enum implements a `displayValue()` method, it will be used for the option label:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';

    public function displayValue(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Shipment',
            self::Shipped => 'Shipped',
        };
    }
}
```

## Customization

All components use blocks that can be overridden:

```twig
<twig:K:Entity:TagManager entity="{{ product }}" tagClass="App\\Entity\\Tag">
    {% block tag_badge %}
        {# Custom tag display #}
        <span class="custom-tag">{{ tag.value }}</span>
    {% endblock %}
</twig:K:Entity:TagManager>
```

## Requirements

- PHP 8.2+
- Symfony 6.4 or 7.0+
- Doctrine ORM
- Symfony UX Live Component

## License

MIT

## Contributing

Contributions welcome! This bundle aims to provide generic, reusable components for Symfony applications.
