<?php

namespace Quantum\Tests\Unit\Lang\Helpers;

use Quantum\Lang\Factories\LangFactory;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
use Quantum\Lang\Lang;

class LangHelperFunctionsTest extends AppTestCase
{
    private Lang $lang;

    public function setUp(): void
    {
        parent::setUp();

        $this->lang = LangFactory::get();
    }

    public function testLangHelperCurrentLang(): void
    {
        $this->assertEquals('en', current_lang());

        $this->lang->setLang('am');

        $this->assertEquals('am', current_lang());
    }

    public function testLangHelperLangUrlSegmentIsTheConfiguredSegment(): void
    {
        config()->set('lang.url_segment', 1);

        $this->assertSame(1, lang_url_segment());

        config()->set('lang.url_segment', 3);

        $this->assertSame(3, lang_url_segment());
    }

    public function testLangHelperLangUrlSegmentMovesPastTheModulePrefixWhenTheSettingIsOne(): void
    {
        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/api/[:alpha:2]?/posts', 'PostController', 'posts'))->prefix('api'),
            []
        ));

        config()->set('lang.url_segment', 1);

        $this->assertSame(2, lang_url_segment());
    }

    public function testLangHelperLangUrlSegmentIsNotMovedWhenTheSettingIsNotOne(): void
    {
        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/api/x/[:alpha:2]?/posts', 'PostController', 'posts'))->prefix('api'),
            []
        ));

        config()->set('lang.url_segment', 3);

        $this->assertSame(3, lang_url_segment());
    }

    public function testLangHelperT(): void
    {
        $this->assertEquals('Testing', t('custom.test'));

        $this->assertEquals('Information about the new feature', t('custom.info', ['new']));
    }

    public function testLangHelperUnderscoreT(): void
    {
        ob_start();

        _t('custom.test');

        $output = ob_get_clean();

        $this->assertEquals('Testing', $output);
    }

    public function testLangHelperTFail(): void
    {
        $this->assertEquals('custom.non_existing_key', t('custom.non_existing_key'));
    }
}
