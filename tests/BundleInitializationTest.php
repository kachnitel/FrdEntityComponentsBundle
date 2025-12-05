<?php

namespace Kachnitel\EntityComponentsBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\ComponentFactory;
use Twig\Environment;

class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Set synthetic services
        $container->set('doctrine.orm.entity_manager', $this->createMock(\Doctrine\ORM\EntityManagerInterface::class));
        $container->set('test.file_handler', $this->createMock(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class));
        $container->set('test.logger', $this->createMock(\Psr\Log\LoggerInterface::class));
    }

    public function testBundleIsRegistered(): void
    {
        $container = self::getContainer();

        $bundles = self::$kernel->getBundles();

        $this->assertArrayHasKey('KachnitelEntityComponentsBundle', $bundles);
    }

    public function testTwigComponentServiceIsAvailable(): void
    {
        $container = self::getContainer();

        $this->assertTrue($container->has('ux.twig_component.component_factory'));
        $componentFactory = $container->get('ux.twig_component.component_factory');
        $this->assertInstanceOf(ComponentFactory::class, $componentFactory);
    }

    public function testTwigIsConfigured(): void
    {
        $container = self::getContainer();

        $this->assertTrue($container->has('twig'));
        $twig = $container->get('twig');
        $this->assertInstanceOf(Environment::class, $twig);
    }

    public function testComponentsAreRegistered(): void
    {
        $container = self::getContainer();

        // Test that component services exist in the container
        $this->assertTrue($container->has('Kachnitel\EntityComponentsBundle\Components\TagManager'));
        $this->assertTrue($container->has('Kachnitel\EntityComponentsBundle\Components\AttachmentManager'));

        /** @var ComponentFactory $componentFactory */
        $componentFactory = $container->get('ux.twig_component.component_factory');

        // Test that components can be created via the factory
        $tagManager = $componentFactory->get('K:Entity:TagManager');
        $this->assertNotNull($tagManager);

        $attachmentManager = $componentFactory->get('K:Entity:AttachmentManager');
        $this->assertNotNull($attachmentManager);
    }
}
