<?php

declare(strict_types=1);

namespace Adrum\Inertia\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class InertiaPageExistsRule implements Rule
{
    private array $pageDirectories;
    private array $pageExtensions;

    public function __construct()
    {
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
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInertiaCall($node)) {
            return [];
        }

        $pageName = $this->extractPageName($node);
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

    private function isInertiaCall(Node $node): bool
    {
        // Check for Inertia::render() static calls
        if ($node instanceof StaticCall) {
            if (
                $node->class instanceof Node\Name &&
                (string) $node->class === 'Inertia' &&
                $node->name instanceof Node\Identifier &&
                $node->name->name === 'render'
            ) {
                return true;
            }
        }

        // Check for inertia() helper function calls
        if ($node instanceof Node\Expr\FuncCall) {
            if (
                $node->name instanceof Node\Name &&
                (string) $node->name === 'inertia'
            ) {
                return true;
            }
        }

        // Check for $this->inertia() method calls in controllers
        if ($node instanceof MethodCall) {
            if (
                $node->name instanceof Node\Identifier &&
                $node->name->name === 'inertia'
            ) {
                return true;
            }
        }

        return false;
    }

    private function extractPageName(Node $node): ?string
    {
        $args = null;

        if ($node instanceof StaticCall || $node instanceof Node\Expr\FuncCall) {
            $args = $node->args;
        } elseif ($node instanceof MethodCall) {
            $args = $node->args;
        }

        if ($args === null || count($args) === 0) {
            return null;
        }

        $firstArg = $args[0]->value;
        if ($firstArg instanceof String_) {
            return $firstArg->value;
        }

        return null;
    }

    private function pageExists(string $pageName): bool
    {
        // Convert dot notation to file path (e.g., "Auth/Login" or "Auth.Login")
        $pagePath = str_replace(['.', '/'], DIRECTORY_SEPARATOR, $pageName);

        foreach ($this->pageDirectories as $directory) {
            foreach ($this->pageExtensions as $extension) {
                $fullPath = $directory . DIRECTORY_SEPARATOR . $pagePath . $extension;

                if (file_exists($fullPath)) {
                    return true;
                }
            }
        }

        return false;
    }
}
