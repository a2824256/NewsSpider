<?php

use Beanbun\Beanbun;

require_once(__DIR__ . '/vendor/autoload.php');

\Beanbun\Queue\MemoryQueue::server('127.0.0.1', '2207');