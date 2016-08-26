<?php
//$before = microtime(true);
// Uncomment for memory usage testing

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/private/vendor/autoload.php';

$file = __DIR__ . '/private/app/components/components.php';
$dir = __DIR__ . '/private/app/components';
$dir = 'http://www.altavea.cz/data/images/53738794450e0/a.270x167.jpg';

$dir = __DIR__ . '/private/app/components';


$fs = new \Webiik\Filesystem();

$arr = [
    '/Users/mihi/Sites/skeletons/webiik/example/test',
    '/Users/mihi/Sites/skeletons/webiik/example/test/hello',
    '/Users/mihi/Sites/skeletons/webiik/example/test/hello/dolly',
    '/Users/mihi/Sites/skeletons/webiik/example/test/hello/dolly/molly',
];

//print_r($fs->mkdir(__DIR__.'/test/hello/dolly/molly'));



//echo mime_content_type($dir);

//$dir = 'http://localhost/skeletons/webiik/example/test.zip';
//$dir = 'http://localhost/skeletons/webiik/example/testicek.html';
//$dir = 'http://localhost/skeletons/webiik/example/';
//$dir = 'http://www.altavea.cz/data/images/53738794450e0/a.270x167.jpg';
//$dir = 'http://www.altavea.cz/';


//if (file_exists($dir)) {
//    $fs = new \FilesystemIterator($dir);
//    echo $fs->isDir() ? 'Dir' : 'File';
//}

//print_r(glob($dir.'*'));

echo '<br/><br/>Peak memory usage: ' . (memory_get_peak_usage() / 1000000) . ' MB';
echo '<br/>End memory usage: ' . (memory_get_usage() / 1000000) . ' MB';
exit;


// Bootstrap app
require __DIR__ . ' /private/app / bootstrap . php';

//$after = microtime(true);
//echo ' < br /><br />Execution time: '. ($after-$before) . ' sec';