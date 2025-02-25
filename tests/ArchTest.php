<?php

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Assert;

use function Orchestra\Testbench\package_path;
use function Pest\testDirectory;

arch()->preset()->php();
arch()->preset()->laravel();
arch()->preset()->security();
arch()->preset()->relaxed();

// We do not want to use base carbon, but the variant from illuminate, since its easily mocked
arch('avoid using base carbon')
    ->expect('Carbon\\Carbon')
    ->not->toBeUsed();

// Disallow factory aliases, since they are unnecessary
// NOTE This test causes a huge amount of assertions
test('avoid using facade aliases', function () {
    /** @var SplFileInfo[] $files */
    $files = [
        ...File::allFiles(package_path('src')),
        ...File::allFiles(config_path()),
        ...File::allFiles(testDirectory()),
    ];

    $aliases = Facade::defaultAliases()->keys();

    foreach ($files as $file) {
        $content = $file->getContents();

        $aliases->each(fn ($alias) => Assert::assertStringNotContainsString(
            "use $alias;",
            $content,
            "{$file->getFilename()} contains a use statement for facade alias '$alias'"
        ));
    }
});

arch('commands use attribute')
    ->expect('HttpAutomock\Console\Commands')
    ->toHaveAttribute('Symfony\Component\Console\Attribute\AsCommand');

test('tests end with the correct suffix', function () {
    $files = [
        ...File::allFiles(testDirectory('Feature')),
        ...File::allFiles(testDirectory('Unit')),
        new SplFileInfo(testDirectory('ArchTest.php')),
        new SplFileInfo(testDirectory('TestServerTest.php')),
    ];
    $fileNames = collect($files)->map(fn (SplFileInfo $file) => $file->getFilename());

    expect($fileNames)->each->toEndWith('Test.php');
});
