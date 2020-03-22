<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\Resolvers;

class PathResolver
{
    private string $baseDir;

    /**
     * PathResolver constructor.
     * @param string $baseDir
     */
    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function getRelativePath(string $file): string
    {
        return str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file);
    }

}
