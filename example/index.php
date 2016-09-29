<?php
//$before = microtime(true);
// Uncomment for memory usage testing

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

// Bootstrap app
require __DIR__ . '/private/app/bootstrap.php';

echo '<br/><br/>Peak memory usage: ' . (memory_get_peak_usage() / 1000000) . ' MB';
echo '<br/>End memory usage: ' . (memory_get_usage() / 1000000) . ' MB';

// Uncomment for getting of all defined PHP vars
//print_r(get_defined_vars());

//$after = microtime(true);
//echo ' < br /><br />Execution time: '. ($after-$before) . ' sec';