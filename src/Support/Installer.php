<?php

namespace A2\A2Commerce\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Installer
{
    private const ENV_KEYS = [
        'A2_PAYPAL_MODE' => 'sandbox',
        'A2_PAYPAL_SECRET' => '',
        'A2_PAYPAL_CLIENT_ID' => '',
        'A2_PAYPAL_WEBHOOK_ID' => '',
        'A2_ORDER_PREFIX' => 'SP-OD',
        'A2_TAX_RATE' => '0',
        'A2_SHIPPING_FEE' => '0',
        'A2_CURRENCY' => 'USD',
        'A2_CURRENCY_SYMBOL' => '$',
        'A2_CURRENCY_CONVERSION_RATE' => '130',
    ];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubsPath,
        private readonly string $appBasePath
    ) {
    }

    /**
     * Install fresh assets and env keys.
     *
     * @return array{copied: array, env: array}
     */
    public function install(bool $overwrite = true, bool $touchEnv = true): array
    {
        $copied = $this->copyStubs($overwrite);
        $envChanges = $touchEnv ? $this->ensureEnvKeys() : [];

        return ['copied' => $copied, 'env' => $envChanges];
    }

    /**
     * Update simply re-runs install with overwrite.
     */
    public function update(bool $touchEnv = true): array
    {
        return $this->install(true, $touchEnv);
    }

    /**
     * Remove copied assets and env keys.
     *
     * @return array{removed: array, env: array}
     */
    public function uninstall(bool $touchEnv = true): array
    {
        $removed = $this->removeStubTargets();
        $env = $touchEnv ? $this->removeEnvKeys() : [];

        return ['removed' => $removed, 'env' => $env];
    }

    private function copyStubs(bool $overwrite): array
    {
        $results = ['copied' => [], 'skipped' => []];
        $stubFiles = $this->files->allFiles($this->stubsPath);

        foreach ($stubFiles as $file) {
            /** @var \SplFileInfo $file */
            $relative = ltrim(Str::after($file->getPathname(), $this->stubsPath), '/\\');
            [$root, $subPath] = $this->splitRoot($relative);
            $target = $this->targetPath($root, $subPath);

            if ($target === null) {
                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));

            if (!$overwrite && $this->files->exists($target)) {
                $results['skipped'][] = $target;
                continue;
            }

            $this->files->copy($file->getPathname(), $target);
            $results['copied'][] = $target;
        }

        return $results;
    }

    private function splitRoot(string $relative): array
    {
        $parts = explode('/', $relative, 2);
        $root = $parts[0] ?? '';
        $rest = $parts[1] ?? '';

        return [$root, $rest];
    }

    private function targetPath(string $root, string $subPath): ?string
    {
        return match ($root) {
            'app' => $this->appPath($subPath),
            'config' => $this->pathJoin($this->appBasePath, 'config', $subPath),
            'database' => $this->pathJoin($this->appBasePath, 'database', $subPath),
            'resources' => $this->pathJoin($this->appBasePath, 'resources', $subPath),
            default => null,
        };
    }

    private function appPath(string $relative): string
    {
        $parts = explode('/', $relative);
        if (isset($parts[0]) && $parts[0] !== '') {
            $parts[0] = Str::studly($parts[0]);
        }

        return $this->pathJoin($this->appBasePath, 'app', implode('/', $parts));
    }

    private function pathJoin(string ...$parts): string
    {
        return collect($parts)
            ->filter(fn ($p) => $p !== '')
            ->map(fn ($p) => trim($p, '/\\'))
            ->implode(DIRECTORY_SEPARATOR);
    }

    private function ensureEnvKeys(): array
    {
        $paths = [
            $this->pathJoin($this->appBasePath, '.env'),
            $this->pathJoin($this->appBasePath, '.env.example'),
        ];

        $added = [];

        foreach ($paths as $envPath) {
            $existing = $this->files->exists($envPath) ? $this->files->get($envPath) : '';
            $addedKeys = [];
            $updated = $this->appendEnvBlock($existing, $addedKeys);

            if ($updated !== $existing) {
                $this->files->put($envPath, $updated);
                $added[$envPath] = $addedKeys;
            } else {
                $added[$envPath] = [];
            }
        }

        return $added;
    }

    private function appendEnvBlock(string $current, ?array &$addedKeys = []): string
    {
        $addedKeys = [];
        $lines = rtrim($current) === '' ? [] : preg_split('/\r\n|\r|\n/', $current);
        $presentKeys = $this->extractExistingKeys($lines);

        foreach (self::ENV_KEYS as $key => $value) {
            if (!in_array($key, $presentKeys, true)) {
                $addedKeys[] = $key;
            }
        }

        if ($addedKeys === []) {
            return $current;
        }

        $block = [];
        $block[] = '# A2 CONFIGURATION';
        foreach ($addedKeys as $key) {
            $block[] = $key . '=' . self::ENV_KEYS[$key];
        }

        $merged = array_merge($lines, $lines ? [''] : [], $block);

        return implode(PHP_EOL, $merged) . PHP_EOL;
    }

    private function extractExistingKeys(array $lines): array
    {
        $keys = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key] = explode('=', $line, 2);
            $keys[] = trim($key);
        }

        return $keys;
    }

    private function removeEnvKeys(): array
    {
        $paths = [
            $this->pathJoin($this->appBasePath, '.env'),
            $this->pathJoin($this->appBasePath, '.env.example'),
        ];

        $removed = [];

        foreach ($paths as $envPath) {
            if (!$this->files->exists($envPath)) {
                $removed[$envPath] = [];
                continue;
            }

            $content = $this->files->get($envPath);
            $updated = $this->stripEnvKeys($content, $removedKeys);

            if ($updated !== $content) {
                $this->files->put($envPath, $updated);
            }

            $removed[$envPath] = $removedKeys;
        }

        return $removed;
    }

    private function stripEnvKeys(string $content, ?array &$removedKeys = []): string
    {
        $removedKeys = [];
        $lines = rtrim($content) === '' ? [] : preg_split('/\r\n|\r|\n/', $content);
        $remaining = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '# A2 CONFIGURATION')) {
                // Drop the comment; only keep if something remains below.
                continue;
            }

            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                if (array_key_exists($key, self::ENV_KEYS)) {
                    $removedKeys[] = $key;
                    continue;
                }
            }

            $remaining[] = $line;
        }

        // Normalize extra blank lines.
        $normalized = [];
        foreach ($remaining as $line) {
            if ($line === '' && ($normalized === [] || end($normalized) === '')) {
                continue;
            }
            $normalized[] = $line;
        }

        return $normalized === [] ? '' : implode(PHP_EOL, $normalized) . PHP_EOL;
    }

    private function removeStubTargets(): array
    {
        $removed = [];
        $stubFiles = $this->files->allFiles($this->stubsPath);

        foreach ($stubFiles as $file) {
            /** @var \SplFileInfo $file */
            $relative = ltrim(Str::after($file->getPathname(), $this->stubsPath), '/\\');
            [$root, $subPath] = $this->splitRoot($relative);
            $target = $this->targetPath($root, $subPath);

            if ($target === null || !$this->files->exists($target)) {
                continue;
            }

            $this->files->delete($target);
            $removed[] = $target;
            $this->pruneEmptyParents(dirname($target), $this->rootPathFor($root));
        }

        return $removed;
    }

    private function rootPathFor(string $root): ?string
    {
        return match ($root) {
            'app' => $this->pathJoin($this->appBasePath, 'app'),
            'config' => $this->pathJoin($this->appBasePath, 'config'),
            'database' => $this->pathJoin($this->appBasePath, 'database'),
            'resources' => $this->pathJoin($this->appBasePath, 'resources'),
            default => null,
        };
    }

    private function pruneEmptyParents(string $path, ?string $stopAt): void
    {
        if ($stopAt === null) {
            return;
        }

        $stopAt = rtrim($stopAt, '/\\');
        $path = rtrim($path, '/\\');

        while (str_starts_with($path, $stopAt)) {
            if (!$this->files->exists($path) || !$this->files->isDirectory($path)) {
                break;
            }

            $contents = array_diff(scandir($path) ?: [], ['.', '..']);
            if ($contents !== []) {
                break;
            }

            $this->files->deleteDirectory($path);
            if ($path === $stopAt) {
                break;
            }

            $path = dirname($path);
        }
    }
}

