<?php

namespace Quantum\Tests\Unit\Module\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;

class RedirectTemplatesTest extends TestCase
{
    #[DataProvider('redirectTemplateProvider')]
    public function testTemplatesDoNotRepeatTheManualReferrerFallback(string $template): void
    {
        $files = $this->templateFiles($template);

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/get_referrer\(\)\s*\?\?\s*base_url\(\)/',
                (string) file_get_contents($file),
                $file
            );
        }
    }

    #[DataProvider('redirectTemplateProvider')]
    public function testTemplatesGoBackWithTheBackRedirect(string $template): void
    {
        $backRedirects = 0;

        foreach ($this->templateFiles($template) as $file) {
            $backRedirects += preg_match_all('/return back\(/', (string) file_get_contents($file));
        }

        $this->assertGreaterThan(0, $backRedirects);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function redirectTemplateProvider(): array
    {
        return [
            'demo-web' => ['DemoWeb'],
            'toolkit' => ['Toolkit'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function templateFiles(string $template): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__, 4) . '/src/Module/Templates/' . $template . '/src')
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php.tpl')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
