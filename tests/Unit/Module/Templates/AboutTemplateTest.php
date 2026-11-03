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

    public function testStarTextsAreTranslated(): void
    {
        $this->assertStringContainsString("_t('common.star.message')", $this->template);
        $this->assertStringContainsString("_t('common.star.button')", $this->template);
        $this->assertStringContainsString("_t('common.star.badge_alt')", $this->template);
    }

    public function testAboutPageHasNoHardCodedStarText(): void
    {
        $this->assertStringNotContainsString('starring Quantum', $this->template);
        $this->assertStringNotContainsString('Star on GitHub', $this->template);
        $this->assertStringNotContainsString('alt="GitHub stars"', $this->template);
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
            '#<img [^>]*src="https://img\.shields\.io/github/stars/quantum-php/framework[^"]*"[^>]*alt="<\?php _t\(\'common\.star\.badge_alt\'\); \?>"[^>]*>#',
            $this->template
        );
    }

    public function testStarMessageComesBeforeTheCliCommands(): void
    {
        $star = strpos($this->template, "_t('common.star.message')");
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
