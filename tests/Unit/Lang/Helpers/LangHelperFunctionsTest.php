<?php

namespace Quantum\Tests\Unit\Lang\Helpers;

use Quantum\Lang\Factories\LangFactory;
use Quantum\Lang\Lang;
use Quantum\Tests\Unit\AppTestCase;

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
