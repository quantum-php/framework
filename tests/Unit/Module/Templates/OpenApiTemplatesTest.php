<?php

namespace Quantum\Tests\Unit\Module\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use OpenApi\Annotations\OpenApi;
use PHPUnit\Framework\TestCase;
use OpenApi\Generator;

class OpenApiTemplatesTest extends TestCase
{
    private string $directory = '';

    /**
     * @var callable|null
     */
    private $autoloader = null;

    public function tearDown(): void
    {
        if ($this->autoloader !== null) {
            spl_autoload_unregister($this->autoloader);
            $this->autoloader = null;
        }

        if ($this->directory !== '' && is_dir($this->directory)) {
            array_map('unlink', glob($this->directory . '/*') ?: []);
            rmdir($this->directory);
        }
    }

    #[DataProvider('apiTemplateProvider')]
    public function testApiTemplatesProduceAValidOpenApiSpecification(string $template): void
    {
        $this->assertTrue($this->generate($template)->validate());
    }

    #[DataProvider('apiTemplateProvider')]
    public function testApiTemplatesDescribeTheApiAndItsSecurity(string $template): void
    {
        $spec = $this->generateSpecification($template);

        $this->assertNotEmpty($spec['info']['title']);
        $this->assertArrayHasKey('bearer_token', $spec['components']['securitySchemes']);
    }

    #[DataProvider('apiTemplateProvider')]
    public function testApiTemplatesDescribeEveryOperationWithASummaryAndResponses(string $template): void
    {
        $spec = $this->generateSpecification($template);

        $this->assertNotEmpty($spec['paths']);

        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $this->assertNotEmpty($operation['summary'] ?? null, "$method $path has no summary");
                $this->assertNotEmpty($operation['responses'] ?? null, "$method $path has no responses");
            }
        }
    }

    #[DataProvider('apiTemplateProvider')]
    public function testApiTemplatesDescribeTheApiWithAttributesOnly(string $template): void
    {
        $templates = glob(dirname(__DIR__, 4) . '/src/Module/Templates/' . $template . '/src/Controllers/OpenApi/*.php.tpl') ?: [];

        $this->assertNotEmpty($templates);

        foreach ($templates as $file) {
            $this->assertStringNotContainsString('@OA\\', (string) file_get_contents($file), basename($file));
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function apiTemplateProvider(): array
    {
        return [
            'default-api' => ['DefaultApi'],
            'demo-api' => ['DemoApi'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSpecification(string $template): array
    {
        return json_decode($this->generate($template)->toJson(), true);
    }

    /**
     * Renders the OpenAPI controllers of a module template and generates the specification the way the
     * openapi command does. swagger-php reads the classes by reflection, so they get an autoloader.
     */
    private function generate(string $template): OpenApi
    {
        $moduleNamespace = 'Modules\\' . $template;
        $namespace = $moduleNamespace . '\\Controllers\\OpenApi';

        $this->directory = sys_get_temp_dir() . '/quantum-openapi-' . $template . '-' . uniqid();
        mkdir($this->directory, 0777, true);

        $templates = glob(dirname(__DIR__, 4) . '/src/Module/Templates/' . $template . '/src/Controllers/OpenApi/*.php.tpl') ?: [];

        $this->assertNotEmpty($templates);

        foreach ($templates as $file) {
            $content = str_replace(
                ['{{MODULE_NAMESPACE}}', '{{MODULE_NAME}}'],
                [$moduleNamespace, 'api'],
                (string) file_get_contents($file)
            );

            file_put_contents($this->directory . '/' . basename($file, '.tpl'), $content);
        }

        $this->autoloader = function (string $class) use ($namespace): void {
            if (str_starts_with($class, $namespace . '\\')) {
                $file = $this->directory . '/' . substr($class, strlen($namespace) + 1) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            }
        };

        spl_autoload_register($this->autoloader);

        $openApi = (new Generator())->generate([$this->directory]);

        $this->assertNotNull($openApi);

        return $openApi;
    }
}
