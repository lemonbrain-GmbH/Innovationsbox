<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainWebp\Job;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Package\LemonbrainWebp\Job\CreateWebpThumbnails\Version8;
use Concrete\Package\LemonbrainWebp\Job\CreateWebpThumbnails\Version9;

if (class_exists("\Concrete\Core\Job\JobQueue")) {
    class CreateWebpThumbnails extends Version9 {}
} else {
    class CreateWebpThumbnails extends Version8 {}
}
