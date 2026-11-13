<?php

declare(strict_types=1);

namespace LemonbrainServices\Command;

use Concrete\Core\Config\Repository\Repository;

defined('C5_EXECUTE') or die('Access Denied.');

class DisplayDebugErrorsCommandHandler
{
    private $config;
    private const CONFIG_PATH = 'concrete.debug.';

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function setAttribute($key, $value)
    {
        $this->config->save(self::CONFIG_PATH . $key, $value);
    }

    public function __invoke(DisplayDebugErrorsCommand $command)
    {
        $this->setAttribute('display_errors', false);
    }
}
