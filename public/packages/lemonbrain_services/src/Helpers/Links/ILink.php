<?php

declare(strict_types=1);

namespace Helpers\Links;

interface ILink
{
    public function transformToLink(
        string $str,
        string $title = '',
        string $innerText = '',
        string $linkClasses = '',
        string $metaPropName = '',
    ): string;
}
