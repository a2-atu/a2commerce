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

    private const ROUTE_MARK_START = '// >>> A2Commerce Routes START';
    private const ROUTE_MARK_END = '// >>> A2Commerce Routes END';
    private const ROUTE_BLOCK = <<<'PHP'
// >>> A2Commerce Routes START
Route::prefix('a2/payment')->group(function () {
    Route::post('/paypal/webhook', [\App\Http\Controllers\A2\Commerce\PaymentController::class, 'webhookPayPal'])->name('api.payment.paypal.webhook');
});
// >>> A2Commerce Routes END
PHP;

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubsPath,
        private readonly string $appBasePath
    ) {}

    /**
     * Install fresh assets and env keys.
     *
     * @return array{copied: array, env: array, routes: array}
     */
    public function install(bool $overwrite = true, bool $touchEnv = true): array
    {
        $copied = $this->copyStubs($overwrite);
        $envChanges = $touchEnv ? $this->ensureEnvKeys() : [];
        $routes = $this->ensureRoutes();

        return ['copied' => $copied, 'env' => $envChanges, 'routes' => $routes];
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
     * @return array{removed: array, env: array, routes: array}
     */
    public function uninstall(bool $touchEnv = true): array
    {
        $removed = $this->removeStubTargets();
        $env = $touchEnv ? $this->removeEnvKeys() : [];
        $routes = $this->removeRoutes();

        return ['removed' => $removed, 'env' => $env, 'routes' => $routes];
    }

    private function copyStubs(bool $overwrite): array
    {
        $results = ['copied' => [], 'skipped' => []];
        $stubFiles = $this->files->allFiles($this->stubsPath);

        foreach ($stubFiles as $file) {
            /** @var \SplFileInfo $file */
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
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
        $root = trim($root, '/\\');

        $applicationRoots = [
            'app' => '',
            'controllers' => 'Http/Controllers',
            'models' => 'Models',
            'services' => 'Services',
            'notifications' => 'Notifications',
            'listeners' => 'Listeners',
            'jobs' => 'Jobs',
            'events' => 'Events',
        ];

        if (array_key_exists($root, $applicationRoots)) {
            return $this->appPathWithPrefix($applicationRoots[$root], $subPath);
        }

        return match ($root) {
            'config' => $this->pathJoin($this->appBasePath, 'config', $subPath),
            'migrations' => $this->pathJoin($this->appBasePath, 'database', 'migrations', $subPath),
            'database' => $this->pathJoin($this->appBasePath, 'database', $subPath),
            'resources' => $this->pathJoin($this->appBasePath, 'resources', $subPath),
            default => null,
        };
    }

    private function appPath(string $relative): string
    {
        return $this->appPathWithPrefix('', $relative);
    }

    private function appPathWithPrefix(string $prefix, string $relative): string
    {
        $relative = $this->normalizeAppRelative($relative);
        $segments = [$this->appBasePath, 'app'];

        if ($prefix !== '') {
            $segments[] = trim($prefix, '/\\');
        }

        if ($relative !== '') {
            $segments[] = $relative;
        }

        return $this->pathJoin(...$segments);
    }

    private function normalizeAppRelative(string $relative): string
    {
        $relative = ltrim($relative, '/\\');
        if ($relative === '') {
            return '';
        }

        $parts = explode('/', $relative);
        if (isset($parts[0]) && $parts[0] !== '') {
            $parts[0] = Str::studly($parts[0]);
        }

        return implode('/', $parts);
    }

    private function pathJoin(string ...$parts): string
    {
        return collect($parts)
            ->filter(fn($p) => $p !== '')
            ->map(fn($p) => trim($p, '/\\'))
            ->implode(DIRECTORY_SEPARATOR);
    }

    public function ensureEnvKeys(): array
    {
        $paths = [
            $this->pathJoin($this->appBasePath, '.env'),
            $this->pathJoin($this->appBasePath, '.env.example'),
        ];

        $added = [];

        foreach ($paths as $envPath) {
            // Mirror Vormia behavior: only touch env files if they already exist.
            if (! $this->files->exists($envPath)) {
                $added[$envPath] = [];
                continue;
            }

            $existing = $this->files->get($envPath);
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

    public function removeEnvKeys(): array
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

    public function ensureRoutes(): array
    {
        $apiPath = $this->pathJoin($this->appBasePath, 'routes', 'api.php');
        $updated = false;
        $importAdded = false;

        $contents = $this->files->exists($apiPath)
            ? $this->files->get($apiPath)
            : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";

        if (!str_contains($contents, 'Illuminate\\Support\\Facades\\Route')) {
            $contents = preg_replace('/<\\?php\\s*/', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n", $contents, 1, $count);
            if ($count === 0) {
                $contents = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n" . ltrim($contents, "<?php");
            }
            $importAdded = true;
            $updated = true;
        }

        if (!str_contains($contents, self::ROUTE_MARK_START)) {
            $contents = rtrim($contents) . "\n\n" . self::ROUTE_BLOCK . "\n";
            $updated = true;
        }

        if ($updated) {
            $this->files->ensureDirectoryExists(dirname($apiPath));
            $this->files->put($apiPath, $contents);
        }

        return [
            'path' => $apiPath,
            'added' => $updated,
            'import_added' => $importAdded,
        ];
    }

    public function removeRoutes(): array
    {
        $apiPath = $this->pathJoin($this->appBasePath, 'routes', 'api.php');
        if (!$this->files->exists($apiPath)) {
            return ['path' => $apiPath, 'removed' => false];
        }

        $contents = $this->files->get($apiPath);
        $pattern = sprintf(
            '#\\n?%s.*?%s\\s*\\n?#s',
            preg_quote(self::ROUTE_MARK_START, '#'),
            preg_quote(self::ROUTE_MARK_END, '#')
        );

        $updated = preg_replace($pattern, "\n", $contents, 1, $count);

        if ($count > 0) {
            $normalized = preg_replace("/[\r\n]{3,}/", "\n\n", $updated ?? '');
            $this->files->put($apiPath, rtrim($normalized) . "\n");
        }

        return ['path' => $apiPath, 'removed' => $count > 0];
    }

    private function stripEnvKeys(string $content, ?array &$removedKeys = []): string
    {
        $removedKeys = [];
        $lines = rtrim($content) === '' ? [] : preg_split('/\r\n|\r|\n/', $content);
        $remaining = [];

        foreach ($lines as $line) {
            // Skip the A2 CONFIGURATION comment line
            if (str_contains($line, '# A2 CONFIGURATION')) {
                continue;
            }

            // Check if this line contains an A2 env key
            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $key = trim($key);
                
                if (array_key_exists($key, self::ENV_KEYS)) {
                    $removedKeys[] = $key;
                    continue; // Skip this line
                }
            }

            $remaining[] = $line;
        }

        // Normalize extra blank lines (remove 3+ consecutive newlines)
        $normalized = preg_replace("/[\r\n]{3,}/", "\n\n", implode(PHP_EOL, $remaining));
        
        return rtrim($normalized) . PHP_EOL;
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
        $root = trim($root, '/\\');

        $applicationRoots = [
            'app' => '',
            'controllers' => 'Http/Controllers',
            'models' => 'Models',
            'services' => 'Services',
            'notifications' => 'Notifications',
            'listeners' => 'Listeners',
            'jobs' => 'Jobs',
            'events' => 'Events',
        ];

        if (array_key_exists($root, $applicationRoots)) {
            return $this->pathJoin($this->appBasePath, 'app', $applicationRoots[$root]);
        }

        return match ($root) {
            'config' => $this->pathJoin($this->appBasePath, 'config'),
            'migrations' => $this->pathJoin($this->appBasePath, 'database', 'migrations'),
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

