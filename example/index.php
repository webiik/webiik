<?php
//$before = microtime(true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

// Bootstrap app
require __DIR__ . '/private/app/bootstrap.php';

//$after = microtime(true);
//echo '<br/><br/>Execution time: '. ($after-$before) . ' sec';