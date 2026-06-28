<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Module;

use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Module\Exceptions\ModuleException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use ReflectionException;
use Quantum\Di\Di;
use Exception;

/**
 * Class ModuleManager
 * @package Quantum\Module
 */
class ModuleManager
{
    public const DEFAULT_TEMPLATE = 'DemoWeb';

    protected FileSystem $fs;

    protected bool $optionEnabled;

    private string $moduleName;

    private string $template;

    private string $modulePath;

    private string $assetsPath;

    private string $templatePath;

    private bool $withAssets;

    private string $modulesConfigPath;

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct(string $moduleName, string $template, bool $enabled, bool $withAssets = false)
    {
        $this->fs = FileSystemFactory::get();

        $this->moduleName = $moduleName;

        $this->withAssets = $withAssets;
        $this->optionEnabled = $enabled;
        $this->template = $template;

        $this->assetsPath = assets_dir() . DS . $moduleName;
        $this->modulePath = modules_dir() . DS . $moduleName;
        $this->templatePath = __DIR__ . DS . 'Templates' . DS . ucfirst($template);
        $this->modulesConfigPath = base_dir() . DS . 'shared' . DS . 'config' . DS . 'modules.php';
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @throws ModuleException|ExceptionInterface|Exception
     */
    public function addModuleConfig(): void
    {
        if (!$this->fs->isDirectory($this->modulePath)) {
            throw ModuleException::missingModuleDirectory();
        }

        if (!Di::isRegistered(ModuleLoader::class)) {
            Di::register(ModuleLoader::class);
        }

        $moduleConfigs = Di::get(ModuleLoader::class)->getModuleConfigs();

        foreach ($moduleConfigs as $module => $options) {
            if ($module == $this->moduleName || $options['prefix'] == strtolower($this->moduleName)) {
                throw ModuleException::moduleAlreadyExists($this->moduleName);
            }
        }

        $moduleConfigs[$this->moduleName] = $this->getModuleOptions($this->moduleName);

        $this->updateModuleConfigFile($moduleConfigs);
    }

    /**
     * @throws Exception
     */
    public function writeContents(): void
    {
        if (!$this->fs->isDirectory(modules_dir())) {
            $this->fs->makeDirectory(modules_dir());
        }

        $copiedTemplates = $this->copyDirectoryWithTemplates($this->templatePath . DS . 'src', $this->modulePath);

        if ($this->withAssets) {
            $copiedAssets = $this->copyAssets($this->templatePath . DS . 'assets', $this->assetsPath);
        }

        if (!$this->verifyModuleFilesCreated(array_merge($copiedTemplates, $copiedAssets ?? []))) {
            throw ModuleException::moduleCreationIncomplete();
        }
    }

    /**
     * @return array<string>
     * @throws Exception
     */
    private function copyDirectoryWithTemplates(string $src, string $dst): array
    {
        return $this->copyDirectory($src, $dst, true);
    }

    /**
     * @return array<string>
     * @throws Exception
     */
    private function copyAssets(string $src, string $dst): array
    {
        return $this->copyDirectory($src, $dst, false);
    }

    /**
     * @param array<string> $copiedFiles
     * @return array<string>
     * @throws Exception
     */
    private function copyDirectory(string $src, string $dst, bool $processTemplates, array $copiedFiles = []): array
    {
        if (!$this->fs->isDirectory($src)) {
            throw ModuleException::missingModuleTemplate($this->template);
        }

        if (!$this->fs->isDirectory($dst)) {
            $this->fs->makeDirectory($dst);
        }

        $dir = $this->fs->listDirectory($src);

        if (!is_array($dir)) {
            throw ModuleException::directoryListingFailed($src);
        }

        foreach ($dir as $file) {
            $srcPath = $file;
            $dstPath = str_replace($src, $dst, $file);

            if ($this->fs->isDirectory($srcPath)) {
                $copiedFiles = $this->copyDirectory($srcPath, $dstPath, $processTemplates, $copiedFiles);
            } else {
                if ($processTemplates) {
                    $this->processTemplates($srcPath, $dstPath);
                } else {
                    $this->fs->copy($srcPath, $dstPath);
                }
                $copiedFiles[] = $dstPath;
            }
        }

        return $copiedFiles;
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function processTemplates(string $srcPath, string &$dstPath): void
    {
        $dstPath = str_replace('.php.tpl', '.php', $dstPath);
        $content = $this->fs->get($srcPath);

        if (!is_string($content)) {
            return;
        }

        $processedContent = $this->replacePlaceholders($content);
        $this->fs->put($dstPath, $processedContent);
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function replacePlaceholders(string $content): string
    {
        $placeholders = [
            '{{MODULE_NAMESPACE}}' => request()->getModuleBaseNamespace() . '\\' . $this->getModuleName(),
            '{{MODULE_NAME}}' => $this->getModuleName(),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function getModuleOptions(string $module): array
    {
        return [
            'prefix' => $this->template == self::DEFAULT_TEMPLATE ? '' : strtolower($module),
            'enabled' => $this->optionEnabled,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $moduleConfigs
     * @throws ExceptionInterface
     */
    private function updateModuleConfigFile(array $moduleConfigs): void
    {
        $this->fs->put(
            $this->modulesConfigPath,
            "<?php\n\nreturn " . export($moduleConfigs) . ";\n"
        );
    }

    /**
     * @param array<string> $copiedFiles
     */
    protected function verifyModuleFilesCreated(array $copiedFiles): bool
    {
        foreach ($copiedFiles as $file) {
            if (!$this->fs->exists($file)) {
                return false;
            }
        }
        return true;
    }
}
