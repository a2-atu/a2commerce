<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use A2\A2Commerce\A2Commerce;

class ExampleTest extends TestCase
{
    public function testVersionConstantIsPresent(): void
    {
        $this->assertNotEmpty(A2Commerce::VERSION);
    }
}

