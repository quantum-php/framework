<?php

namespace Quantum\Tests\Unit\Config;

use Quantum\Tests\_root\modules\Test\Transformers\PostTransformer;
use Quantum\Transformer\Contracts\TransformerInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Tests\Unit\AppTestCase;
use Dflydev\DotAccessData\Data;
use Quantum\Config\Config;
use Quantum\Config\Setup;

class ConfigTest extends AppTestCase
{
    private Config $config;

    public function setUp(): void
    {
        parent::setUp();

        config()->flush();

        $this->config = new Config();
    }

    public function testConfigLoad(): void
    {
        $this->assertEmpty($this->config->all());

        $this->config->load(new Setup('config', 'app'));

        $this->assertNotEmpty($this->config->all());

        $this->assertInstanceOf(Data::class, $this->config->all());
    }

    public function testConfigImport(): void
    {
        $this->config->load(new Setup('config', 'app'));

        $this->assertNull($this->config->get('database.default'));

        $this->config->import(new Setup('config', 'database'));

        $this->assertNotNull($this->config->get('database.default'));

        $this->assertEquals('sqlite', $this->config->get('database.default'));
    }

    public function testImportLoadsModuleConfig(): void
    {
        $this->config->import(new Setup('config', 'dependencies', true, 'Test'));

        $this->assertSame(
            PostTransformer::class,
            $this->config->get('dependencies.' . TransformerInterface::class)
        );
    }

    public function testModuleConfigTakesPrecedenceOverSharedConfig(): void
    {
        $moduleConfig = PROJECT_ROOT . DS . 'modules' . DS . 'Test' . DS . 'config' . DS . 'app.php';
        $this->assertFileDoesNotExist($moduleConfig);

        try {
            $this->createFile($moduleConfig, "<?php\n\nreturn ['name' => 'Module app'];\n");
            $this->config->import(new Setup('config', 'app', true, 'Test'));

            $this->assertSame('Module app', $this->config->get('app.name'));
        } finally {
            $this->removeFile($moduleConfig);
        }
    }

    public function testImportFallsBackToSharedConfig(): void
    {
        $this->config->import(new Setup('config', 'app', true, 'Test'));

        $this->assertSame('Quantum PHP Framework', $this->config->get('app.name'));
    }

    public function testNonModuleConfigIsResolvedFromApplicationBase(): void
    {
        $filename = 'config_resolution_probe_' . uniqid();
        $applicationConfigDirectory = PROJECT_ROOT . DS . 'config';
        $applicationConfig = $applicationConfigDirectory . DS . $filename . '.php';
        $workingDirectory = sys_get_temp_dir() . DS . 'quantum-config-' . uniqid();
        $workingConfig = $workingDirectory . DS . 'config';
        $workingConfigFile = $workingConfig . DS . $filename . '.php';
        $currentDirectory = getcwd();
        $createdApplicationConfigDirectory = !is_dir($applicationConfigDirectory);

        if ($createdApplicationConfigDirectory) {
            mkdir($applicationConfigDirectory, 0777, true);
        }
        mkdir($workingConfig, 0777, true);
        $this->createFile($applicationConfig, "<?php\n\nreturn ['source' => 'application'];\n");
        file_put_contents($workingConfigFile, "<?php\n\nreturn ['source' => 'working directory'];\n");

        try {
            chdir($workingDirectory);
            $this->config->import(new Setup('config', $filename, false));

            $this->assertSame('application', $this->config->get($filename . '.source'));
        } finally {
            chdir($currentDirectory);
            $this->removeFile($applicationConfig);
            if ($createdApplicationConfigDirectory) {
                rmdir($applicationConfigDirectory);
            }
            unlink($workingConfigFile);
            rmdir($workingConfig);
            rmdir($workingDirectory);
        }
    }

    public function testImportIfExistsReturnsFalseWithoutChangingConfig(): void
    {
        $this->assertFalse($this->config->importIfExists(new Setup('config', 'missing_optional')));
        $this->assertNull($this->config->all());
    }

    public function testImportIfExistsTreatsEmptyConfigAsPresent(): void
    {
        $filePath = PROJECT_ROOT . DS . 'shared' . DS . 'config' . DS . 'empty_optional.php';
        $this->assertFileDoesNotExist($filePath);

        try {
            $this->createFile($filePath, "<?php\n\nreturn [];\n");

            $this->assertTrue($this->config->importIfExists(new Setup('config', 'empty_optional')));
            $this->assertSame([], $this->config->get('empty_optional'));
        } finally {
            $this->removeFile($filePath);
        }
    }

    public function testImportIfExistsPreservesCollision(): void
    {
        $this->config->import(new Setup('config', 'app'));

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config key `app` is already in use');

        $this->config->importIfExists(new Setup('config', 'app'));
    }

    public function testImportDoesNotFallBackWhenHierarchyIsDisabled(): void
    {
        try {
            $this->config->import(new Setup('config', 'app', false, 'Test'));
            $this->fail('Expected a missing config exception');
        } catch (ConfigException $exception) {
            $this->assertFalse($this->config->has('app'));
        }
    }

    public function testLoadDoesNotReplacePreviouslyLoadedConfig(): void
    {
        $this->config->load(new Setup('config', 'app'));
        $this->config->load(new Setup('config', 'database'));

        $this->assertSame('Quantum PHP Framework', $this->config->get('name'));
        $this->assertNull($this->config->get('default'));
    }

    public function testImportingNonExistingConfigFile(): void
    {
        $this->expectException(ConfigException::class);

        $this->expectExceptionMessage('File `config' . DS . 'somefile` not found!');

        $this->config->import(new Setup('config', 'somefile'));
    }

    public function testCollisionAtImporting(): void
    {
        $this->config->import(new Setup('config', 'app'));

        $this->expectException(ConfigException::class);

        $this->expectExceptionMessage('Config key `app` is already in use');

        $this->config->import(new Setup('config', 'app'));
    }

    public function testConfigHas(): void
    {
        $this->config->import(new Setup('config', 'app'));

        $this->assertTrue($this->config->has('app.debug'));

        $this->assertTrue($this->config->has('app.test'));

        $this->assertFalse($this->config->has('app.none'));
    }

    public function testConfigGet(): void
    {
        $this->config->import(new Setup('config', 'lang'));

        $this->assertIsArray($this->config->get('lang.supported'));

        $this->assertEquals('Default Value', $this->config->get('not-exists', 'Default Value'));

        $this->assertNull($this->config->get('not-exists'));
    }

    public function testConfigSet(): void
    {
        $this->assertFalse($this->config->has('new-value'));

        $this->config->set('new-value', 'New Value');

        $this->assertTrue($this->config->has('new-value'));

        $this->assertEquals('New Value', $this->config->get('new-value'));

        $this->config->set('other.nested', 'Nested Value');

        $this->assertTrue($this->config->has('other.nested'));

        $this->assertEquals('Nested Value', $this->config->get('other.nested'));
    }

    public function testConfigDelete(): void
    {
        $this->config->import(new Setup('config', 'app'));

        $this->assertNotNull($this->config->get('app.test'));

        $this->config->delete('app.test');

        $this->assertFalse($this->config->has('app.test'));

        $this->assertNull($this->config->get('app.test'));
    }

    public function testGetBoolReadsTrueValues(): void
    {
        foreach ([true, 1, '1', 'true', 'TRUE', 'on', 'yes'] as $value) {
            $this->config->set('flag', $value);

            $this->assertTrue($this->config->getBool('flag'), var_export($value, true));
        }
    }

    public function testGetBoolReadsFalseValues(): void
    {
        foreach ([false, 0, '0', 'false', 'off', 'no', '', 'abc', []] as $value) {
            $this->config->set('flag', $value);

            $this->assertFalse($this->config->getBool('flag', true), var_export($value, true));
        }
    }

    public function testGetBoolReturnsDefaultForMissingOrNullKey(): void
    {
        $this->assertFalse($this->config->getBool('missing'));
        $this->assertTrue($this->config->getBool('missing', true));

        $this->config->set('flag', null);

        $this->assertTrue($this->config->getBool('flag', true));
    }

    public function testGetBoolReadsNestedKey(): void
    {
        $this->config->set('other', 'value');
        $this->config->set('app.debug', 'true');

        $this->assertTrue($this->config->getBool('app.debug'));
    }

    public function testGetIntReadsWholeNumbers(): void
    {
        foreach ([[587, 587], ['587', 587], ['-3', -3], ['0', 0], [0, 0]] as [$value, $expected]) {
            $this->config->set('number', $value);

            $this->assertSame($expected, $this->config->getInt('number'), var_export($value, true));
        }
    }

    public function testGetIntReturnsDefaultForMissingOrNullKey(): void
    {
        $this->assertSame(0, $this->config->getInt('missing'));
        $this->assertSame(1000, $this->config->getInt('missing', 1000));

        $this->config->set('number', null);

        $this->assertSame(1000, $this->config->getInt('number', 1000));
    }

    public function testGetIntThrowsForTextThatIsNotAWholeNumber(): void
    {
        foreach (['abc', '1.5', '', '12abc', 1.5, []] as $value) {
            $this->config->set('number', $value);

            try {
                $this->config->getInt('number');
                $this->fail('No exception for ' . var_export($value, true));
            } catch (ConfigException $e) {
                $this->assertStringContainsString('`number`', $e->getMessage());
                $this->assertStringContainsString('not a whole number', $e->getMessage());
            }
        }
    }

    public function testGetRawValueIsNotAffectedByTypedReaders(): void
    {
        $this->config->set('number', '587');

        $this->assertSame('587', $this->config->get('number'));
    }

    public function testConfigFlush(): void
    {
        $this->config->import(new Setup('config', 'app'));

        $this->assertNotEmpty($this->config->all());

        $this->config->flush();

        $this->assertEmpty($this->config->all());
    }
}
