<?php

namespace AsmundStavdahl\Php2Js;

require __DIR__ . '/../vendor/autoload.php';

echo Php2Js::convert(__DIR__ . '/test-og-php2js.php');
