<?php

declare(strict_types=1);

namespace Worksome\PhpInsightsApp\GitHub;

use NunoMaduro\PhpInsights\Domain\Details;
use Worksome\PhpInsightsApp\Resolvers\PathResolver;

class Comment
{
    private string $body;
    private int $line;
    private string $path;

    /**
     * Comment constructor.
     * @param Details $detail
     * @param string $title
     * @param PathResolver $pathResolver
     */
    public function __construct(Details $detail, string $title, PathResolver $pathResolver)
    {
        $this->line = $detail->hasLine() ? $detail->getLine() : 1;
        $this->body = self::formatBody($detail, $title);
        $this->path = $pathResolver->getRelativePath($detail->getFile());

    }

    private static function formatBody(Details $detail, string $title): string
    {
        $message = "[{$title}] ";

        if ($detail->hasMessage()) {
            $message .= $detail->getMessage();
        }

        return $message;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

}
