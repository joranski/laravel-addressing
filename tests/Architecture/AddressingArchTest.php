<?php

declare(strict_types=1);

/**
 * Architecture / package-isolation guard tests for the addressing subsystem.
 */

use Joranski\Addressing\Contracts\AddressVerifier;

/**
 * @return iterable<string>
 */
function addressingPhpFilesIn(string $absoluteDir): iterable
{
    if (! is_dir($absoluteDir)) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absoluteDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
            yield $file->getPathname();
        }
    }
}

function addressingFqcnFromPath(string $absolutePath): string
{
    $marker = '/src/';
    $pos = strpos($absolutePath, $marker);

    if ($pos === false) {
        throw new InvalidArgumentException("Path is not under src/: {$absolutePath}");
    }

    $relativeToSrc = substr($absolutePath, $pos + strlen($marker));
    $withoutExt = substr($relativeToSrc, 0, -4);

    return 'Joranski\\Addressing\\'.str_replace('/', '\\', $withoutExt);
}

test('every class under Joranski\\Addressing declares strict_types=1', function (): void {
    $offenders = [];
    $srcDir = dirname(__DIR__, 2).'/src';

    foreach (addressingPhpFilesIn($srcDir) as $file) {
        $source = file_get_contents($file);

        if (! preg_match('/^\s*<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', $source)) {
            $offenders[] = $file;
        }
    }

    expect($offenders)->toBeEmpty(
        message: 'Every file under src/ must start with `declare(strict_types=1);`. Offenders: '.implode(', ', $offenders),
    );
});

test('every concrete class in Joranski\\Addressing\\Verifiers implements AddressVerifier', function (): void {
    $offenders = [];
    $srcDir = dirname(__DIR__, 2).'/src/Verifiers';

    foreach (addressingPhpFilesIn($srcDir) as $file) {
        $fqcn = addressingFqcnFromPath($file);

        if (! class_exists($fqcn)) {
            continue;
        }

        $reflection = new ReflectionClass($fqcn);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            continue;
        }

        if (! $reflection->implementsInterface(AddressVerifier::class)) {
            $offenders[] = $fqcn;
        }
    }

    expect($offenders)->toBeEmpty(
        message: 'Every concrete class in Verifiers must implement AddressVerifier. Offenders: '.implode(', ', $offenders),
    );
});

test('every DTO under Joranski\\Addressing\\Data is readonly', function (): void {
    $offenders = [];
    $srcDir = dirname(__DIR__, 2).'/src/Data';

    foreach (addressingPhpFilesIn($srcDir) as $file) {
        $fqcn = addressingFqcnFromPath($file);

        if (! class_exists($fqcn)) {
            continue;
        }

        $reflection = new ReflectionClass($fqcn);

        if ($reflection->isInterface() || $reflection->isAbstract() || $reflection->isTrait()) {
            continue;
        }

        if (! $reflection->isReadOnly()) {
            $offenders[] = $fqcn;
        }
    }

    expect($offenders)->toBeEmpty(
        message: 'Every DTO in Data must be declared `readonly`. Offenders: '.implode(', ', $offenders),
    );
});

test('no class under Joranski\\Addressing imports App\\Models\\* or App\\Filament\\Resources\\Addresses\\*', function (): void {
    $offenders = [];
    $srcDir = dirname(__DIR__, 2).'/src';

    foreach (addressingPhpFilesIn($srcDir) as $file) {
        $source = file_get_contents($file);

        if (preg_match_all('/^\s*use\s+([^;]+);/m', $source, $matches)) {
            foreach ($matches[1] as $import) {
                $import = trim($import);

                if (str_starts_with($import, 'App\\Models\\')) {
                    $offenders[] = sprintf('%s imports %s', $file, $import);
                }

                if (str_starts_with($import, 'App\\Filament\\Resources\\Addresses\\')) {
                    $offenders[] = sprintf('%s imports %s', $file, $import);
                }
            }
        }
    }

    expect($offenders)->toBeEmpty(
        message: 'Package source must not depend on host App\\Models\\* or App\\Filament\\Resources\\Addresses\\*. Offenders: '.implode("\n", $offenders),
    );
});
