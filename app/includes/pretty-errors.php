<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// collect errors here
$phpErrors = [];

// custom error handler just pushes into our array
function prettyErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    global $phpErrors;
    $phpErrors[] = compact('errno','errstr','errfile','errline');
    return true; // don’t run PHP’s internal handler
}

set_error_handler('prettyErrorHandler');

// at shutdown, if we have errors, render them all in one <pre>
register_shutdown_function(function() {
    global $phpErrors;
    if (empty($phpErrors)) {
        return;
    }

    echo '<pre class="php-error">';
    $count = count($phpErrors);
    foreach ($phpErrors as $i => $e) {
        echo "PHP ERROR [{$e['errno']}]: {$e['errstr']}\n";
        echo "In {$e['errfile']} on line {$e['errline']}\n";
        // output an <hr> (as HTML) between errors, but not after the last
        if ($i < $count - 1) {
            echo "<hr>\n";
        }
    }
    echo '</pre>';
});
