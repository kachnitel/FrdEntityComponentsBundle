<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;

/**
 * Rewrites ManyToMany join table and column names to match what Doctrine would
 * have generated had the concrete class been used directly in the trait mapping.
 *
 * ## Problem
 *
 * Traits in this bundle declare associations targeting interfaces:
 *
 *   #[ORM\ManyToMany(targetEntity: TagInterface::class)]
 *   private Collection $tags;
 *
 * Doctrine generates the join table name from the interface short name *before*
 * `ResolveTargetEntityListener` resolves the concrete class. This produces:
 *
 *   product_tag_interface          instead of  product_tag
 *   attachment_interface_id        instead of  uploaded_file_id
 *
 * Blindly stripping "_interface" is wrong when the concrete class name differs
 * from the interface name (e.g. `UploadedFile implements AttachmentInterface`
 * would produce `product_attachment` instead of `product_uploaded_file`).
 *
 * ## Solution
 *
 * This subscriber receives the app's `doctrine.orm.resolve_target_entities`
 * mapping (injected by {@see JoinTableNormalizerPass} at compile time) and
 * builds a replacement map:
 *
 *   snake_case(InterfaceShortName) → snake_case(ConcreteShortName)
 *   e.g. "attachment_interface"   → "uploaded_file"
 *        "tag_interface"          → "tag"
 *
 * On each `loadClassMetadata` event every MANY_TO_MANY owning-side join table
 * name and its column names are rewritten by directly mutating the typed
 * `JoinTableMapping` object. Associations that already use concrete class names
 * are unaffected.
 *
 * ## Why #[AsDoctrineListener] instead of EventSubscriber
 *
 * Services implementing `Doctrine\Common\EventSubscriber` and tagged via
 * autoconfigure with `doctrine.event_subscriber` are removed by Symfony's
 * container optimizer when they have no explicit dependents ("Usages: none").
 * The service is removed before Doctrine's event manager collects it, so the
 * listener never fires. `#[AsDoctrineListener]` registers the listener directly
 * on the Doctrine event manager at compile time and survives optimization.
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
final class JoinTableNormalizerSubscriber
{
    /**
     * snake_case interface name → snake_case concrete name.
     * Built once in the constructor from the injected FQCN map.
     *
     * @var array<string, string>
     */
    private array $replacements;

    /**
     * @param array<string, string> $resolvedEntities
     *        interface FQCN → concrete FQCN,
     *        as configured in doctrine.orm.resolve_target_entities.
     *        Injected by JoinTableNormalizerPass at compile time.
     */
    public function __construct(array $resolvedEntities = [])
    {
        $this->replacements = [];

        foreach ($resolvedEntities as $interfaceFqcn => $concreteFqcn) {
            $interfaceSnake = $this->toSnakeCase($this->shortName($interfaceFqcn));
            $concreteSnake  = $this->toSnakeCase($this->shortName($concreteFqcn));

            if ($interfaceSnake !== $concreteSnake) {
                $this->replacements[$interfaceSnake] = $concreteSnake;
            }
        }
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        if ($this->replacements === []) {
            return;
        }

        $metadata = $event->getClassMetadata();

        foreach ($metadata->associationMappings as $mapping) {
            // Only owning-side ManyToMany has a joinTable property.
            // ManyToManyInverseSideMapping does not — and must not be touched.
            if (!($mapping instanceof ManyToManyOwningSideMapping)) {
                continue;
            }

            // Directly mutate the typed JoinTableMapping object.
            // Writing via ArrayAccess ($mapping['joinTable'] = ...) does NOT
            // update the underlying property — Doctrine ORM 3.x mapping objects
            // implement ArrayAccess for BC only; the schema generator reads
            // typed properties exclusively.
            $mapping->joinTable->name = $this->replace($mapping->joinTable->name);

            foreach ($mapping->joinTable->joinColumns as $column) {
                $column->name = $this->replace($column->name);
            }

            foreach ($mapping->joinTable->inverseJoinColumns as $column) {
                $column->name = $this->replace($column->name);
            }
        }
    }

    private function replace(string $name): string
    {
        foreach ($this->replacements as $from => $to) {
            $name = str_replace($from, $to, $name);
        }

        return $name;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Converts a PascalCase class name to snake_case.
     * e.g. "UploadedFile" → "uploaded_file", "TagInterface" → "tag_interface"
     */
    private function toSnakeCase(string $name): string
    {
        $snake = preg_replace('/([A-Z])/', '_$1', lcfirst($name)) ?? $name;

        return strtolower($snake);
    }
}
