<?php

declare(strict_types=1);

namespace Adrum\Inertia\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 */
class InertiaPageExistsRule implements Rule
{
    private array $pageDirectories;
    private array $pageExtensions;
    private FileHelper $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;

        // Default Laravel/Inertia configuration
        $this->pageDirectories = [
            'resources/js/Pages',
            'resources/js/pages',
            'resources/ts/Pages',
            'resources/ts/pages',
            'resources/vue/Pages',
            'resources/vue/pages',
            'resources/react/Pages',
            'resources/react/pages',
        ];

        $this->pageExtensions = [
            '.vue',
            '.jsx',
            '.tsx',
            '.js',
            '.ts',
        ];
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof StaticCall) {
            return [];
        }

        $pageName = $this->extractInertiaPageName($node);

        if ($pageName === null) {
            return [];
        }

        if (!$this->pageExists($pageName)) {
            return [
                RuleErrorBuilder::message(
                    sprintf('Inertia page "%s" does not exist on disk.', $pageName)
                )->build(),
            ];
        }

        return [];
    }

    private function extractInertiaPageName(StaticCall $node): ?string
    {
        if (!$node->class instanceof Node\Name) {
            return null;
        }

        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        $className = (string) $node->class;
        $methodName = $node->name->name;

        // Inertia::render('PageName', [...])
        if (($className === 'Inertia' || $className === 'Inertia\Inertia') && $methodName === 'render') {
            return $this->getStringArgument($node, 0);
        }

        // Route::inertia('/path', 'PageName', [...])
        if (($className === 'Route' || $className === 'Illuminate\Support\Facades\Route') && $methodName === 'inertia') {
            return $this->getStringArgument($node, 1);
        }

        return null;
    }

    private function getStringArgument(StaticCall $node, int $index): ?string
    {
        $args = $node->args;

        if (count($args) <= $index) {
            return null;
        }

        $arg = $args[$index]->value;
        if ($arg instanceof String_) {
            return $arg->value;
        }

        return null;
    }

    private function pageExists(string $pageName): bool
    {
        // Convert dot notation to file path (e.g., "Auth/Login" or "Auth.Login")
        $pagePath = str_replace(['.', '/'], DIRECTORY_SEPARATOR, $pageName);

        // Get the project root directory (where composer.json is located)
        $projectRoot = $this->findProjectRoot();

        foreach ($this->pageDirectories as $directory) {
            foreach ($this->pageExtensions as $extension) {
                $fullPath = $projectRoot . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $pagePath . $extension;
                $normalizedPath = $this->fileHelper->normalizePath($fullPath);

                if (is_file($normalizedPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findProjectRoot(): string
    {
        $currentDir = getcwd();

        // Walk up the directory tree looking for composer.json
        while ($currentDir !== DIRECTORY_SEPARATOR) {
            if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        // Fallback to current working directory
        return getcwd();
    }
}
