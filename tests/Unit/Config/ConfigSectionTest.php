<?php

namespace Quantum\Tests\Unit\Config;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;
use stdClass;

class ConfigSectionTest extends AppTestCase
{
    public function testGetReturnsTheValueOrTheDefault(): void
    {
        $section = new ConfigSection(['host' => '127.0.0.1', 'prefix' => null]);

        $this->assertSame('127.0.0.1', $section->get('host'));
        $this->assertNull($section->get('missing'));
        $this->assertSame('localhost', $section->get('missing', 'localhost'));
        $this->assertNull($section->get('prefix', 'cache_'));
    }

    public function testGetReadsDotKeys(): void
    {
        $section = new ConfigSection(['cache' => ['enabled' => true, 'ttl' => 60]]);

        $this->assertSame(60, $section->get('cache.ttl'));
        $this->assertSame(['enabled' => true, 'ttl' => 60], $section->get('cache'));
        $this->assertNull($section->get('cache.prefix'));
    }

    public function testHasReportsKeysThatExist(): void
    {
        $section = new ConfigSection(['cache' => ['ttl' => null]]);

        $this->assertTrue($section->has('cache'));
        $this->assertTrue($section->has('cache.ttl'));
        $this->assertFalse($section->has('cache.prefix'));
        $this->assertFalse($section->has('missing'));
    }

    public function testGetBoolReadsTheSection(): void
    {
        $section = new ConfigSection(['two_fa' => 'yes', 'cache' => ['enabled' => 'off'], 'empty' => null]);

        $this->assertTrue($section->getBool('two_fa'));
        $this->assertFalse($section->getBool('cache.enabled', true));
        $this->assertTrue($section->getBool('empty', true));
        $this->assertTrue($section->getBool('missing', true));
    }

    public function testGetIntReadsTheSection(): void
    {
        $section = new ConfigSection(['port' => '6379', 'cache' => ['ttl' => 60], 'empty' => null]);

        $this->assertSame(6379, $section->getInt('port'));
        $this->assertSame(60, $section->getInt('cache.ttl'));
        $this->assertSame(25, $section->getInt('empty', 25));
        $this->assertSame(25, $section->getInt('missing', 25));
    }

    public function testGetIntThrowsForTextThatIsNotAWholeNumber(): void
    {
        $section = new ConfigSection(['port' => 'abc']);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `port` has the value `abc`, which is not a whole number.');

        $section->getInt('port');
    }

    public function testAllReturnsTheWholeSection(): void
    {
        $data = ['cache' => false, 'debug' => true, 'nested' => ['a' => 1]];

        $this->assertSame($data, (new ConfigSection($data))->all());
        $this->assertSame([], (new ConfigSection())->all());
    }

    public function testSectionCutsOutASmallerSection(): void
    {
        $section = new ConfigSection(['session' => ['remember_lifetime' => '3600'], 'two_fa' => true]);

        $sessionSection = $section->section('session');

        $this->assertSame(['remember_lifetime' => '3600'], $sessionSection->all());
        $this->assertSame(3600, $sessionSection->getInt('remember_lifetime'));
        $this->assertSame([], $section->section('two_fa')->all());
        $this->assertSame([], $section->section('missing')->all());
    }

    public function testSectionCannotBeChanged(): void
    {
        foreach (['set', 'delete', 'flush', 'load', 'import', 'importIfExists'] as $method) {
            $this->assertFalse(method_exists(ConfigSection::class, $method), $method);
        }
    }

    public function testAllReturnsACopyOfNestedArrays(): void
    {
        $section = new ConfigSection(['redis' => ['options' => ['timeout' => 5]]]);

        $values = $section->all();
        $values['redis']['options']['timeout'] = 99;

        $this->assertSame(5, $section->get('redis.options.timeout'));
    }

    public function testSectionReturnsACopyOfNestedArrays(): void
    {
        $section = new ConfigSection(['redis' => ['options' => ['timeout' => 5]]]);

        $redis = $section->section('redis');
        $values = $redis->all();
        $values['options']['timeout'] = 99;

        $this->assertSame(5, $redis->get('options.timeout'));
        $this->assertSame(5, $section->get('redis.options.timeout'));
    }

    public function testObjectsInASectionAreShared(): void
    {
        $handler = new stdClass();
        $handler->calls = 0;

        $section = new ConfigSection(['handler' => $handler]);

        $section->get('handler')->calls++;

        $this->assertSame($handler, $section->get('handler'));
        $this->assertSame($handler, $section->all()['handler']);
        $this->assertSame(1, $handler->calls);
    }
}
