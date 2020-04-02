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

    public function __construct(Details $detail, string $title, PathResolver $pathResolver)
    {
        $this->line = $detail->hasLine() ? $detail->getLine() : 1;
        $this->body = self::formatBody($detail, $title);
        echo "original file path: {$detail->getFile()}\n";
        $this->path = $pathResolver->getRelativePath($detail->getFile());
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private static function formatBody(Details $detail, string $title): string
    {
        $message = "[{$title}] ";

        if ($detail->hasMessage() && !$detail->hasDiff()) {
            $message .= $detail->getMessage();
        }

        if ($detail->hasDiff()) {
            $message .= "\n```diff\n{$detail->getDiff()}\n```";
        }

        return $message;
    }
}
