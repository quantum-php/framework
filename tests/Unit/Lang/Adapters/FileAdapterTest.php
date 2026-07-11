<?php

namespace Quantum\Tests\Unit\Lang\Adapters;

use Quantum\Lang\Adapters\FileAdapter;
use Quantum\Tests\Unit\AppTestCase;

class FileAdapterTest extends AppTestCase
{
    public function testFileAdapterConstruct(): void
    {
        $adapter = new FileAdapter('en');

        $this->assertInstanceOf(FileAdapter::class, $adapter);
    }

    public function testFileAdapterLoadTranslations(): void
    {
        $adapter = new FileAdapter('en');

        $adapter->loadTranslations();

        $this->assertEquals('Testing', $adapter->get('custom.test'));

        $this->assertEquals('Information about the value feature', $adapter->get('custom.info', ['param' => 'value']));
    }

    public function testFileAdapterGetTranslation(): void
    {
        $adapter = new FileAdapter('en');

        $adapter->loadTranslations();

        $result = $adapter->get('custom.info', ['new']);

        $this->assertEquals('Information about the new feature', $result);
    }
}
