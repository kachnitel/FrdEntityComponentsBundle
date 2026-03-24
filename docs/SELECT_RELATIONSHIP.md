# SelectRelationship

An eager dropdown for editing entity relationships and backed enums in-place.
The component auto-detects whether the target property is a Doctrine entity or a PHP enum.

## Quick Start

```twig
<twig:K:Entity:SelectRelationship :entity="order" property="region" />
```

That's all for an entity relation. The component:
- Loads all `Region` entities from the repository
- Shows a dropdown with the current value selected
- Persists the change immediately on selection

For a backed enum property it works identically — no extra configuration needed.

---

## What's Next?

<details>
<summary><strong>Access control</strong></summary>

Show the editable select only to users with the right role. Everyone else sees a plain read-only label:

```twig
<twig:K:Entity:SelectRelationship
    :entity="order"
    property="region"
    :config="{
        role: 'ROLE_ORDER_REGION_EDIT',
    }"
/>
```

Use `viewRole` to hide the field entirely from users who lack a secondary role:

```twig
:config="{
    role: 'ROLE_EDITOR',
    viewRole: 'ROLE_VIEWER',
}"
```

</details>

<details>
<summary><strong>Filter the option list</strong></summary>

Pass a `filter` array for a simple `findBy()` call — useful for showing only active records:

```twig
:config="{
    filter: { active: true },
}"
```

</details>

<details>
<summary><strong>Custom repository method</strong></summary>

For more complex queries, point at a custom repository method:

```twig
config="{{ {
    repositoryMethod: 'findByRoles',
    repositoryArgs: [['ROLE_TERRITORY_MANAGER']],
} }}"
```

```php
class UserRepository extends ServiceEntityRepository
{
    public function findByRoles(array $roles): array { ... }
}
```

</details>

<details>
<summary><strong>Custom value and display properties</strong></summary>

By default the component reads `id` as the option value and `name` as the label.
Override either with `valueProperty` / `displayProperty`:

```twig
:config="{
    valueProperty: 'code',
    displayProperty: 'label',
}"
```

</details>

<details>
<summary><strong>Enum display labels</strong></summary>

By default the enum case `name` is used as the label (`Active`, `Inactive`, etc.).
Implement a `displayValue()` method on the enum for custom labels:

```php
enum OrderStatus: string
{
    case Pending  = 'pending';
    case Shipped  = 'shipped';

    public function displayValue(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting shipment',
            self::Shipped => 'On its way',
        };
    }
}
```

</details>

<details>
<summary><strong>Labels and placeholder</strong></summary>

```twig
:config="{
    placeholder: '— Select Region —',
    label: 'Delivery Region',
    disableEmpty: false,   // set true to prevent selecting blank
}"
```

</details>

<details>
<summary><strong>Force read-only</strong></summary>

```twig
:config="{ disabled: true }"
```

This is independent of `role` — useful when the read-only state comes from entity logic rather than user permissions.

</details>

<details>
<summary><strong>When to use this vs field components</strong></summary>

| Scenario | Use |
|---|---|
| Small/static option list, persist immediately on change | `K:Entity:SelectRelationship` |
| Large option list (100+ entities), live search, save/cancel | `K:Entity:Field:Relationship` |
| Multi-value collection, large dataset | `K:Entity:Field:Collection` |
| Tagging with colored category badges | `K:Entity:TagManager` |

</details>

<details>
<summary><strong>SelectRelationshipOptions reference</strong></summary>

| Option | Type | Default | Description |
|---|---|---|---|
| `placeholder` | `string` | `'-'` | Empty option text |
| `valueProperty` | `string` | `'id'` | Entity property used as `<option value>` |
| `displayProperty` | `string` | `'name'` | Entity property used as option label |
| `disableEmpty` | `bool` | `false` | Disable the empty/placeholder option |
| `filter` | `array` | `[]` | Simple `findBy()` criteria |
| `repositoryMethod` | `string\|null` | `null` | Custom repository method name |
| `repositoryArgs` | `array` | `[]` | Arguments passed to the custom method |
| `role` | `string\|null` | `null` | Role required to show the editable select |
| `viewRole` | `string\|null` | `null` | Role required to show the read-only label |
| `disabled` | `bool` | `false` | Force read-only regardless of role |
| `label` | `string\|null` | `null` | Label rendered above the select |

</details>
