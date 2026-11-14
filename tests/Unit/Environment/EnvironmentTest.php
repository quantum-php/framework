<?php

namespace Quantum\Tests\Unit\Environment;

use Quantum\Environment\Contracts\EnvironmentInterface;
use Quantum\Environment\Exceptions\EnvException;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\AppContext;
use Quantum\App\App;

class EnvironmentTest extends AppTestCase
{
    private Environment $env;

    public function setUp(): void
    {
        parent::setUp();

        $this->env = environment();
    }

    public function testEnvironmentImplementsEnvironmentInterface(): void
    {
        $this->assertInstanceOf(EnvironmentInterface::class, $this->env);
    }

    public function testEnvironmentGetAppEnv(): void
    {
        $this->assertEquals('testing', $this->env->getAppEnv());
    }

    public function testLoadReportsMissingBootstrapConfig(): void
    {
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer(), $context->getType()));

            $this->expectException(EnvException::class);
            $this->expectExceptionMessage('shared' . DS . 'config' . DS . 'env.php');

            (new Environment())->load();
        } finally {
            App::setContext($context);
        }
    }

    public function testLoadIgnoresModuleBootstrapConfig(): void
    {
        $moduleConfig = PROJECT_ROOT . DS . 'modules' . DS . 'Test' . DS . 'config' . DS . 'env.php';
        $this->assertFileDoesNotExist($moduleConfig);

        try {
            $this->createFile($moduleConfig, "<?php\n\nreturn ['app_env' => 'staging'];\n");
            $this->testRequest('/');

            $environment = new Environment();
            $environment->load();

            $this->assertSame('testing', $environment->getAppEnv());
        } finally {
            $this->removeFile($moduleConfig);
        }
    }

    public function testLoadDoesNotReadBootstrapAgain(): void
    {
        $environment = new Environment();
        $environment->load();
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer(), $context->getType()));

            $environment->load();

            $this->assertSame('testing', $environment->getAppEnv());
        } finally {
            App::setContext($context);
        }
    }

    public function testEnvironmentGetValue(): void
    {
        $this->assertNull($this->env->getValue('NON_EXISTING_KEY'));

        $this->assertNotNull($this->env->getValue('APP_KEY'));

        $this->assertEquals('TRUE', $this->env->getValue('DEBUG'));

        $this->assertEquals('XYZ1234567890ABCDEFG123456789HIGKLMNOPQRSTUVWXYZ0123456789abcdefgh', $this->env->getValue('APP_KEY'));
    }

    public function testEnvironmentGetBool(): void
    {
        $this->setEnvValues([
            'FLAG_TRUE' => 'true',
            'FLAG_UPPER' => 'TRUE',
            'FLAG_ONE' => '1',
            'FLAG_ON' => 'on',
            'FLAG_YES' => 'yes',
            'FLAG_FALSE' => 'false',
            'FLAG_ZERO' => '0',
            'FLAG_EMPTY' => '',
            'FLAG_OFF' => 'off',
            'FLAG_NO' => 'no',
            'FLAG_UNKNOWN' => 'maybe',
        ]);

        foreach (['FLAG_TRUE', 'FLAG_UPPER', 'FLAG_ONE', 'FLAG_ON', 'FLAG_YES'] as $key) {
            $this->assertTrue($this->env->getBool($key), $key);
        }

        foreach (['FLAG_FALSE', 'FLAG_ZERO', 'FLAG_EMPTY', 'FLAG_OFF', 'FLAG_NO', 'FLAG_UNKNOWN'] as $key) {
            $this->assertFalse($this->env->getBool($key, true), $key);
        }
    }

    public function testEnvironmentGetBoolUsesDefaultForMissingKey(): void
    {
        $this->assertFalse($this->env->getBool('NON_EXISTING_KEY'));
        $this->assertTrue($this->env->getBool('NON_EXISTING_KEY', true));
    }

    public function testEnvironmentGetBoolReadsTheTestEnvFile(): void
    {
        $this->assertTrue($this->env->getBool('DEBUG'));
    }

    public function testEnvironmentGetInt(): void
    {
        $this->setEnvValues(['MAIL_PORT' => '587', 'NEGATIVE' => '-5', 'ZERO' => '0']);

        $this->assertSame(587, $this->env->getInt('MAIL_PORT'));
        $this->assertSame(-5, $this->env->getInt('NEGATIVE'));
        $this->assertSame(0, $this->env->getInt('ZERO', 9));
    }

    public function testEnvironmentGetIntUsesDefaultForMissingKey(): void
    {
        $this->assertSame(0, $this->env->getInt('NON_EXISTING_KEY'));
        $this->assertSame(25, $this->env->getInt('NON_EXISTING_KEY', 25));
    }

    /**
     * @dataProvider invalidIntProvider
     */
    public function testEnvironmentGetIntThrowsForInvalidNumber(string $value): void
    {
        $this->setEnvValues(['MAIL_PORT' => $value]);

        $this->expectException(EnvException::class);
        $this->expectExceptionMessage('The environment variable `MAIL_PORT` has the value `' . $value . '`, which is not a whole number.');

        $this->env->getInt('MAIL_PORT');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIntProvider(): array
    {
        return [
            'text' => ['abc'],
            'decimal' => ['1.5'],
            'empty' => [''],
        ];
    }

    public function testEnvironmentGetBoolAndGetIntThrowWhenNotLoaded(): void
    {
        $environment = new Environment();

        try {
            $environment->getBool('DEBUG');
            $this->fail('Expected EnvException');
        } catch (EnvException $e) {
            $this->assertStringContainsString('Environment not loaded', $e->getMessage());
        }

        $this->expectException(EnvException::class);
        $this->expectExceptionMessage('Environment not loaded');

        $environment->getInt('MAIL_PORT');
    }

    public function testEnvironmentHasKey(): void
    {
        $this->assertTrue($this->env->hasKey('APP_KEY'));

        $this->assertFalse($this->env->hasKey('NON_EXISTING_KEY'));
    }

    public function testEnvironmentGetRow(): void
    {
        $this->assertNull($this->env->getRow('NON_EXISTING_KEY'));

        $this->assertEquals('APP_KEY=XYZ1234567890ABCDEFG123456789HIGKLMNOPQRSTUVWXYZ0123456789abcdefgh', $this->env->getRow('APP_KEY'));
    }

    public function testEnvironmentIsTestingInTestEnv(): void
    {
        $this->assertTrue($this->env->isTesting());
        $this->assertFalse($this->env->isProduction());
        $this->assertFalse($this->env->isStaging());
        $this->assertFalse($this->env->isDevelopment());
        $this->assertFalse($this->env->isLocal());
    }

    public function testEnvironmentCheckMethodsWithDifferentEnvs(): void
    {
        $this->setPrivateProperty($this->env, 'appEnv', 'production');
        $this->assertTrue($this->env->isProduction());
        $this->assertFalse($this->env->isTesting());

        $this->setPrivateProperty($this->env, 'appEnv', 'staging');
        $this->assertTrue($this->env->isStaging());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'development');
        $this->assertTrue($this->env->isDevelopment());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'local');
        $this->assertTrue($this->env->isLocal());
        $this->assertFalse($this->env->isProduction());

        $this->setPrivateProperty($this->env, 'appEnv', 'testing');
    }

    public function testEnvironmentAddAndUpdateRow(): void
    {
        $envFilePath = App::getBaseDir() . DS . '.env.testing';
        $originalContent = $this->fs->get($envFilePath);

        $this->assertNull($this->env->getValue('SOMETHING'));

        $this->env->updateRow('SOMETHING', 'Something');

        $this->assertEquals('Something', $this->env->getValue('SOMETHING'));

        $this->env->updateRow('SOMETHING', 'Something_else');

        $this->assertEquals('Something_else', $this->env->getValue('SOMETHING'));

        $this->fs->put($envFilePath, $originalContent);
    }

    public function testEnvironmentGetValueThrowsWhenNotLoaded(): void
    {
        $this->expectException(EnvException::class);
        $this->expectExceptionMessage('Environment not loaded');

        (new Environment())->getValue('APP_KEY');
    }

    public function testEnvironmentUpdateRowThrowsWhenImmutable(): void
    {
        $this->expectException(EnvException::class);
        $this->expectExceptionMessage('The environment is immutable');

        (new Environment())->updateRow('SOMETHING', 'Something');
    }

    public function testEnvironmentUpdateRowThrowsWhenNotLoaded(): void
    {
        $this->expectException(EnvException::class);
        $this->expectExceptionMessage('Environment not loaded');

        (new Environment())->setMutable(true)->updateRow('SOMETHING', 'Something');
    }

    public function testEnvironmentUpdateRowThrowsWhenEnvFileCannotBeRead(): void
    {
        $this->setPrivateProperty($this->env, 'appEnv', 'missing');

        set_error_handler(static fn (): bool => true, E_WARNING);

        try {
            $this->expectException(EnvException::class);
            $this->expectExceptionMessage('.env.missing');

            $this->env->updateRow('APP_KEY', 'Something');
        } finally {
            restore_error_handler();
            $this->setPrivateProperty($this->env, 'appEnv', 'testing');
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function setEnvValues(array $values): void
    {
        $content = $this->getPrivateProperty($this->env, 'envContent');

        $this->setPrivateProperty($this->env, 'envContent', array_merge($content, $values));
    }
}
