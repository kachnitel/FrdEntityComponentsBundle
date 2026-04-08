<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Kachnitel\EntityComponentsBundle\Doctrine\JoinTableNormalizerSubscriber;
use Kachnitel\EntityComponentsBundle\Tests\Doctrine\Fixtures\NormalizerArticle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for JoinTableNormalizerSubscriber.
 *
 * Boots JoinTableNormalizerKernel — which configures `resolve_target_entities`
 * mapping all three bundle interfaces to concrete test fixture classes — and
 * verifies that the actual Doctrine ClassMetadata produced for NormalizerArticle
 * contains join table names derived from the concrete class, not the interface.
 *
 * This is the authoritative test: it exercises the full path from
 *   JoinTableNormalizerPass (compile time) →
 *   JoinTableNormalizerSubscriber (runtime, loadClassMetadata event) →
 *   ClassMetadata.associationMappings (what Doctrine uses for schema generation).
 *
 * If this test passes, `doctrine:migrations:diff` will produce correct table names.
 */
#[CoversClass(JoinTableNormalizerSubscriber::class)]
#[Group('doctrine')]
class JoinTableNormalizerIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return JoinTableNormalizerKernel::class;
    }

    protected function setUp(): void
    {
        // Clear any stale compiled cache before booting so Doctrine rebuilds
        // metadata from the current fixture classes on every run.
        $cacheDir = sys_get_temp_dir() . '/kachnitel_entity_components_bundle/normalizer_tests/cache';
        if (is_dir($cacheDir)) {
            self::removeDirectory($cacheDir);
        }

        self::bootKernel();

        $container = self::getContainer();
        $container->set('test.file_handler', $this->createMock(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class));
        $container->set('test.logger', $this->createMock(\Psr\Log\LoggerInterface::class));
        $container->set('test.security', $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class));

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public static function tearDownAfterClass(): void
    {
        $cacheDir = sys_get_temp_dir() . '/kachnitel_entity_components_bundle/normalizer_tests/cache';
        if (is_dir($cacheDir)) {
            self::removeDirectory($cacheDir);
        }

        parent::tearDownAfterClass();
    }

    // ── TaggableTrait: TagInterface → NormalizerTestTag ───────────────────────

    public function testTagsJoinTableUsesConcreteTagClassName(): void
    {
        $joinTable = $this->getJoinTable('tags');

        $this->assertStringNotContainsString(
            'interface',
            $joinTable->name,
            'Join table name must not contain "interface" — subscriber must have rewritten it'
        );
        $this->assertStringContainsString('normalizer_test_tag', $joinTable->name);
    }

    public function testTagsInverseJoinColumnUsesConcreteTagClassName(): void
    {
        $joinTable = $this->getJoinTable('tags');

        foreach ($joinTable->inverseJoinColumns as $column) {
            $this->assertStringNotContainsString('interface', $column->name);
        }
    }

    // ── AttachableTrait: AttachmentInterface → NormalizerTestAttachment ───────

    public function testAttachmentsJoinTableUsesConcreteAttachmentClassName(): void
    {
        $joinTable = $this->getJoinTable('attachments');

        $this->assertStringNotContainsString('interface', $joinTable->name);
        $this->assertStringContainsString('normalizer_test_attachment', $joinTable->name);
    }

    public function testAttachmentsInverseJoinColumnUsesConcreteAttachmentClassName(): void
    {
        $joinTable = $this->getJoinTable('attachments');

        foreach ($joinTable->inverseJoinColumns as $column) {
            $this->assertStringNotContainsString('interface', $column->name);
        }
    }

    // ── CommentableTrait: CommentInterface → NormalizerTestComment ────────────

    public function testCommentsJoinTableUsesConcreteCommentClassName(): void
    {
        $joinTable = $this->getJoinTable('comments');

        $this->assertStringNotContainsString('interface', $joinTable->name);
        $this->assertStringContainsString('normalizer_test_comment', $joinTable->name);
    }

    public function testCommentsInverseJoinColumnUsesConcreteCommentClassName(): void
    {
        $joinTable = $this->getJoinTable('comments');

        foreach ($joinTable->inverseJoinColumns as $column) {
            $this->assertStringNotContainsString('interface', $column->name);
        }
    }

    // ── Subscriber is registered with the correct resolved entities ───────────

    public function testSubscriberIsRegisteredInContainer(): void
    {
        $subscriber = self::getContainer()->get(JoinTableNormalizerSubscriber::class);
        $this->assertInstanceOf(JoinTableNormalizerSubscriber::class, $subscriber);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function getJoinTable(string $association): \Doctrine\ORM\Mapping\JoinTableMapping
    {
        /** @var EntityManagerInterface $em */
        $em       = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $em->getClassMetadata(NormalizerArticle::class);

        $mapping = $metadata->associationMappings[$association] ?? null;

        $this->assertNotNull($mapping, "Association '{$association}' not found on NormalizerArticle");
        $this->assertInstanceOf(
            ManyToManyOwningSideMapping::class,
            $mapping,
            "Association '{$association}' must be a ManyToMany owning-side mapping"
        );

        return $mapping->joinTable;
    }

    private static function removeDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
