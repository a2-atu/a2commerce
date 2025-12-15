<?php

namespace A2\A2Commerce;

class A2Commerce
{
    public const VERSION = '0.1.2';

    /**
     * Absolute path to the package stubs.
     */
    public static function stubsPath(string $suffix = ''): string
    {
        $base = __DIR__ . '/stubs';

        return $suffix ? $base . '/' . ltrim($suffix, '/') : $base;
    }
}

