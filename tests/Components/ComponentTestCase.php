<?php

namespace Frd\EntityComponentsBundle\Tests\Components;

use Frd\EntityComponentsBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\ComponentFactory;

abstract class ComponentTestCase extends KernelTestCase
{
    protected ComponentFactory $factory;

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
        $container->set('test.file_handler', $this->createMock(\Frd\EntityComponentsBundle\Interface\FileHandlerInterface::class));
        $container->set('test.logger', $this->createMock(\Psr\Log\LoggerInterface::class));

        $this->factory = $container->get('ux.twig_component.component_factory');
    }
}
