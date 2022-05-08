<?php

use AsmundStavdahl\Php2Js\Php2Js;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/functions.php';

echo renderPage(
    body: "<div id='theDiv'>(the div)</div>",
    scripts: [
        Php2Js::convert(__DIR__ . '/../src/test-og-php2js.php'),
    ]
);
