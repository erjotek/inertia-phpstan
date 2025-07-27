<?php

declare(strict_types=1);

namespace Adrum\Inertia\PHPStan;

use PHPStan\PhpDoc\StubFilesExtension;

class InertiaExtension implements StubFilesExtension
{
    /** @inheritDoc */
    public function getFiles(): array
    {
        return [];
    }
}
