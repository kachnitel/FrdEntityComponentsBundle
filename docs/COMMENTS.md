# CommentsManager

Add threaded comments with author attribution and delete confirmation to any entity.

## Quick Start

### 1. Tell Doctrine which class implements `CommentInterface`

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        resolve_target_entities:
            Kachnitel\EntityComponentsBundle\Interface\CommentInterface: App\Entity\Comment
```

### 2. Create a Comment entity

```php
use Kachnitel\EntityComponentsBundle\Interface\CommentInterface;

#[ORM\Entity]
class Comment implements CommentInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }
    public function getText(): ?string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getCreatedBy(): mixed { return $this->createdBy; }
    public function setCreatedBy(mixed $user): static { $this->createdBy = $user; return $this; }
}
```

### 3. Use the trait on any entity

The `CommentableTrait` includes the full Doctrine mapping — no `#[ORM\ManyToMany]`
declaration needed in your entity:

```php
use Kachnitel\EntityComponentsBundle\Interface\CommentableInterface;
use Kachnitel\EntityComponentsBundle\Trait\CommentableTrait;

#[ORM\Entity]
class Article implements CommentableInterface
{
    use CommentableTrait;

    public function __construct() { $this->initializeComments(); }
}
```

### 4. Drop the component into any template

```twig
<twig:K:Entity:CommentsManager
    :entity="article"
    commentClass="App\\Entity\\Comment"
/>
```

Users can post new comments and delete their own (admins can delete any).

---

## What's Next?

<details>
<summary><strong>Join table naming</strong></summary>

The bundle automatically generates join table names from the owning entity and
your concrete comment class, e.g. `Article` + `Comment` → `article_comment`.

This works correctly even when your concrete class name differs from the
interface name. No extra configuration is required.

Comments are cascade-persisted and cascade-removed by default (since a comment
has no meaningful existence outside its parent entity). To override, redeclare
the property in your entity:

```php
class Article implements CommentableInterface
{
    use CommentableTrait;

    #[ORM\ManyToMany(targetEntity: CommentInterface::class, cascade: [])]
    #[ORM\JoinTable(name: 'article_notes')]
    private Collection $comments;
}
```

</details>

<details>
<summary><strong>Read-only display</strong></summary>

Hide the text input and delete buttons — useful for archived content or read-only views:

```twig
<twig:K:Entity:CommentsManager
    :entity="article"
    commentClass="App\\Entity\\Comment"
    :config="{ readOnly: true }"
/>
```

</details>

<details>
<summary><strong>Custom collection property</strong></summary>

If your entity stores comments under a different property name (e.g. `notes`):

```twig
<twig:K:Entity:CommentsManager
    :entity="ticket"
    commentClass="App\\Entity\\Note"
    :config="{ property: 'notes' }"
/>
```

</details>

<details>
<summary><strong>Limiting text length</strong></summary>

Define a `MAX_TEXT_LENGTH` constant on your Comment class and the textarea `maxlength` attribute is set automatically:

```php
class Comment implements CommentInterface
{
    public const MAX_TEXT_LENGTH = 500;
}
```

If the constant is absent, the default limit of `4096` is used.

</details>

<details>
<summary><strong>Author attribution</strong></summary>

The component calls `setCreatedBy()` on the comment before persisting, passing the currently authenticated user. Add the method to your Comment entity and store the relation however suits your app:

```php
#[ORM\ManyToOne]
private ?User $createdBy = null;

public function setCreatedBy(mixed $user): static
{
    $this->createdBy = $user;
    return $this;
}
```

The template shows the author's `name` property (or falls back to casting to string). Own comments are highlighted and can be deleted by their author; admins (`ROLE_ADMIN`) can delete any comment.

</details>

<details>
<summary><strong>Delete confirmation</strong></summary>

Clicking delete once sets a `confirmId` on the component and re-renders the button as "Confirm". Clicking a second time executes the delete. This two-step pattern is built-in and requires no extra configuration.

</details>

<details>
<summary><strong>CommentableTrait reference</strong></summary>

The trait provides `getComments()`, `addComment()`, and `removeComment()`, as
well as the `#[ORM\ManyToMany]` mapping targeting `CommentInterface` with
`cascade: ['persist', 'remove']`. You must:

1. Add `use CommentableTrait;` to your entity
2. Call `$this->initializeComments()` in your entity constructor
3. Configure `resolve_target_entities` in `doctrine.yaml` (see Quick Start)

The join table name is derived from your entity and concrete comment class
names and is normalised automatically.

</details>

<details>
<summary><strong>CommentsManagerOptions reference</strong></summary>

| Option | Type | Default | Description |
|---|---|---|---|
| `readOnly` | `bool` | `false` | Hide the comment form and delete buttons |
| `property` | `string` | `'comments'` | Collection property name on the entity |

</details>

<details>
<summary><strong>CommentInterface reference</strong></summary>

| Method | Return | Required | Notes |
|---|---|---|---|
| `getId()` | `?int` | ✅ | Primary key |
| `getText()` | `?string` | ✅ | Comment body |
| `setText(string $text)` | `static` | ✅ | Called when creating a new comment |
| `getCreatedAt()` | `?\DateTimeImmutable` | ✅ | Shown in the card header |
| `getCreatedBy()` | `mixed` | ✅ | User or any entity — template uses `.name` or casts to string |
| `setCreatedBy(mixed $user)` | — | optional | Called automatically if it exists |

</details>
