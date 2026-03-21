<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures;

enum FieldTestStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
