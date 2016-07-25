<?php
try {

    require __DIR__ . '/private/app.php';

} catch (Exception $e) {

    echo '<h1>Exception</h1>';
    echo 'Message:<br/><b>' . $e->getMessage() . '</b><br/><br/>';
    $file = $e->getFile();
    $pos = strrpos($file, '/');

    echo 'File:<br/>' . substr($file, 0, $pos+1) . '<b>'. substr($file, $pos+1, strlen($file)) . '</b><br/><br/>';
    echo 'Line: <b>' . $e->getLine() . '</b><br/><br/>';
    echo 'Trace:<br/>';
    $trace = explode('#', $e->getTraceAsString());
    unset($trace[0]);
    foreach ($trace as $traceLine) {
        echo '#' . $traceLine . '<br/>';
    }

} catch (Throwable $e) {

}