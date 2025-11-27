# FRD Entity Components Bundle

Reusable Symfony Live Components for entity management. Provides tag and attachment management components that work with any Doctrine entity.

## Features

- **TagManager** - Live Component for managing tags on any entity
- **AttachmentManager** - Live Component for file uploads and attachments
- **Generic interfaces** - Work with your own Tag/Attachment entities
- **Reusable traits** - Easy implementation for entities
- **Framework-agnostic design** - Minimal dependencies

## Installation

```bash
composer require frd/entity-components-bundle
```

## Quick Start

### 1. Create a Tag Entity

```php
use Frd\EntityComponentsBundle\Interface\TagInterface;

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
use Frd\EntityComponentsBundle\Interface\TaggableInterface;
use Frd\EntityComponentsBundle\Trait\TaggableTrait;

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
<twig:FRD:TagManager
    entity="{{ product }}"
    tagClass="App\\Entity\\Tag"
/>
```

## Attachments Quick Start

### 1. Create an Attachment Entity

```php
use Frd\EntityComponentsBundle\Interface\AttachmentInterface;

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
use Frd\EntityComponentsBundle\Interface\AttachableInterface;
use Frd\EntityComponentsBundle\Trait\AttachableTrait;

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
use Frd\EntityComponentsBundle\Interface\FileHandlerInterface;

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
<twig:FRD:AttachmentManager
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
<twig:FRD:TagManager
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
<twig:FRD:AttachmentManager
    entity="{{ product }}"
    attachmentClass="App\\Entity\\UploadedFile"
/>
```

**Required Service:**
You must implement and register a `FileHandlerInterface` service:

```php
use Frd\EntityComponentsBundle\Interface\FileHandlerInterface;
use Frd\EntityComponentsBundle\Interface\AttachmentInterface;
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

## Customization

All components use blocks that can be overridden:

```twig
<twig:FRD:TagManager entity="{{ product }}" tagClass="App\\Entity\\Tag">
    {% block tag_badge %}
        {# Custom tag display #}
        <span class="custom-tag">{{ tag.value }}</span>
    {% endblock %}
</twig:FRD:TagManager>
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
