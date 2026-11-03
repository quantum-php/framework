<?php

namespace Quantum\Tests\Unit\Module\Templates;

use PHPUnit\Framework\TestCase;

class AboutTemplateTest extends TestCase
{
    private string $template;

    public function setUp(): void
    {
        $this->template = (string) file_get_contents(
            dirname(__DIR__, 4) . '/src/Module/Templates/DemoWeb/src/Views/pages/about.php.tpl'
        );
    }

    public function testAboutPageAsksForAStarOnGitHub(): void
    {
        $this->assertStringContainsString('starring Quantum on GitHub', $this->template);
    }

    public function testAboutPageLinksToTheFrameworkRepository(): void
    {
        $this->assertMatchesRegularExpression(
            '#<a [^>]*href="https://github\.com/quantum-php/framework"[^>]*>#',
            $this->template
        );
    }

    public function testStarLinkOpensInANewTabSafely(): void
    {
        $this->assertMatchesRegularExpression(
            '#<a [^>]*href="https://github\.com/quantum-php/framework"[^>]*target="_blank"[^>]*rel="noopener noreferrer"[^>]*>#',
            $this->template
        );
    }

    public function testAboutPageShowsTheStarCountBadge(): void
    {
        $this->assertMatchesRegularExpression(
            '#<img [^>]*src="https://img\.shields\.io/github/stars/quantum-php/framework[^"]*"[^>]*alt="GitHub stars"[^>]*>#',
            $this->template
        );
    }

    public function testStarMessageComesBeforeTheCliCommands(): void
    {
        $star = strpos($this->template, 'starring Quantum on GitHub');
        $commands = strpos($this->template, 'common.cli_commands');

        $this->assertNotFalse($star);
        $this->assertNotFalse($commands);
        $this->assertLessThan($commands, $star);
    }

    public function testAboutPageDoesNotUseTheOldRepositoryName(): void
    {
        $this->assertStringNotContainsString('softberg', $this->template);
    }
}
