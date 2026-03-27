<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Twig;

use DateTime;
use DateTimeImmutable;
use Kachnitel\EntityComponentsBundle\Twig\UtilExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TemplateWrapper;

#[CoversClass(UtilExtension::class)]
#[Group('twig')]
class UtilExtensionTest extends TestCase
{
    private UtilExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UtilExtension();
    }

    // ── getTests() ─────────────────────────────────────────────────────────────

    public function testGetTestsReturnsArray(): void
    {
        $tests = $this->extension->getTests();

        $this->assertIsArray($tests);
        $this->assertNotEmpty($tests);
    }

    public function testGetTestsRegistersObjectTest(): void
    {
        $tests = $this->extension->getTests();
        $testNames = array_map(fn($t) => $t->getName(), $tests);

        $this->assertContains('object', $testNames);
    }

    public function testGetTestsRegistersDatetimeTest(): void
    {
        $tests = $this->extension->getTests();
        $testNames = array_map(fn($t) => $t->getName(), $tests);

        $this->assertContains('datetime', $testNames);
    }

    // ── 'object' test ──────────────────────────────────────────────────────────

    public function testObjectTestReturnsTrueForObject(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest, "object test not found");

        $object = new \stdClass();
        $testCallable = $objectTest->getCallable();

        $this->assertTrue($testCallable($object));
    }

    public function testObjectTestReturnsFalseForString(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest);

        $testCallable = $objectTest->getCallable();

        $this->assertFalse($testCallable('not an object'));
    }

    public function testObjectTestReturnsFalseForInt(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest);

        $testCallable = $objectTest->getCallable();

        $this->assertFalse($testCallable(42));
    }

    public function testObjectTestReturnsFalseForArray(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest);

        $testCallable = $objectTest->getCallable();

        $this->assertFalse($testCallable([1, 2, 3]));
    }

    public function testObjectTestReturnsFalseForNull(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest);

        $testCallable = $objectTest->getCallable();

        $this->assertFalse($testCallable(null));
    }

    public function testObjectTestWithCustomObjectClass(): void
    {
        $tests = $this->extension->getTests();
        $objectTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'object') {
                $objectTest = $test;
                break;
            }
        }

        $this->assertNotNull($objectTest);

        $object = new class {
            public string $property = 'value';
        };

        $testCallable = $objectTest->getCallable();

        $this->assertTrue($testCallable($object));
    }

    // ── 'datetime' test ────────────────────────────────────────────────────────

    public function testDatetimeTestReturnsTrueForDateTime(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $datetime = new DateTime();
        $testCallable = $datetimeTest->getCallable();

        $this->assertTrue($testCallable($datetime));
    }

    public function testDatetimeTestReturnsTrueForDateTimeImmutable(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $datetime = new DateTimeImmutable();
        $testCallable = $datetimeTest->getCallable();

        $this->assertTrue($testCallable($datetime));
    }

    public function testDatetimeTestReturnsFalseForString(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $testCallable = $datetimeTest->getCallable();

        $this->assertFalse($testCallable('2024-01-01'));
    }

    public function testDatetimeTestReturnsFalseForInt(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $testCallable = $datetimeTest->getCallable();

        $this->assertFalse($testCallable(1234567890));
    }

    public function testDatetimeTestReturnsFalseForArray(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $testCallable = $datetimeTest->getCallable();

        $this->assertFalse($testCallable(['date' => '2024-01-01']));
    }

    public function testDatetimeTestReturnsFalseForObject(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $testCallable = $datetimeTest->getCallable();

        $this->assertFalse($testCallable(new \stdClass()));
    }

    public function testDatetimeTestReturnsFalseForNull(): void
    {
        $tests = $this->extension->getTests();
        $datetimeTest = null;

        foreach ($tests as $test) {
            if ($test->getName() === 'datetime') {
                $datetimeTest = $test;
                break;
            }
        }

        $this->assertNotNull($datetimeTest);

        $testCallable = $datetimeTest->getCallable();

        $this->assertFalse($testCallable(null));
    }

    // ── Twig Integration ───────────────────────────────────────────────────────

    public function testObjectTestWorksInTwigTemplate(): void
    {
        $template = $this->createTwigTemplate('{{ value is object ? "yes" : "no" }}');

        $result = $template->render(['value' => new \stdClass()]);
        $this->assertSame('yes', $result);

        $result = $template->render(['value' => 'string']);
        $this->assertSame('no', $result);
    }

    public function testDatetimeTestWorksInTwigTemplate(): void
    {
        $template = $this->createTwigTemplate('{{ value is datetime ? "yes" : "no" }}');

        $result = $template->render(['value' => new DateTime()]);
        $this->assertSame('yes', $result);

        $result = $template->render(['value' => 'string']);
        $this->assertSame('no', $result);
    }

    public function testCombinedTestsInTwigTemplate(): void
    {
        $template = $this->createTwigTemplate('
{% if value is datetime %}
  Datetime: {{ value|date("Y-m-d") }}
{% elseif value is object %}
  Object
{% else %}
  Other
{% endif %}
        ');

        $result = $template->render(['value' => new DateTime('2024-03-26')]);
        $this->assertStringContainsString('Datetime: 2024-03-26', $result);

        $result = $template->render(['value' => new \stdClass()]);
        $this->assertStringContainsString('Object', trim($result));

        $result = $template->render(['value' => 'string']);
        $this->assertStringContainsString('Other', trim($result));
    }

    private function createTwigTemplate(string $template): TemplateWrapper
    {
        $loader = $this->createMock(LoaderInterface::class);

        $twig = new Environment($loader, []);
        $twig->addExtension($this->extension);

        return $twig->createTemplate($template);
    }
}
