# FRD Entity Components Bundle

Reusable Symfony Live Components for entity management. Provides tag and attachment management components that work with any Doctrine entity.

## Features

- **TagManager** - Live Component for managing tags on any entity
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
