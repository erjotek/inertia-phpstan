<?php

declare(strict_types=1);

namespace Adrum\Inertia\PHPStan\Cache;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class InertiaPagesCacheMetaExtension implements ResultCacheMetaExtension
{
    private array $pageDirectories;
    private array $pageExtensions;

    public function __construct()
    {
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

    public function getKey(): string
    {
        return 'inertia-pages-cache-meta';
    }

    public function getHash(): string
    {
        $projectRoot = $this->findProjectRoot();
        $fileData = [];

        foreach ($this->pageDirectories as $directory) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . $directory;
            
            if (!is_dir($fullPath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $extension = '.' . $file->getExtension();
                if (!in_array($extension, $this->pageExtensions, true)) {
                    continue;
                }

                $fileData[] = $file->getPathname() . ':' . $file->getMTime();
            }
        }

        sort($fileData);
        return md5(implode('|', $fileData));
    }

    private function findProjectRoot(): string
    {
        $currentDir = getcwd();

        while ($currentDir !== DIRECTORY_SEPARATOR) {
            if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        return getcwd();
    }
}