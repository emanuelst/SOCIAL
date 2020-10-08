<?php

// https://stackoverflow.com/a/34629093/841052
function hasSuffix($var)
{
    // returns whether the input ends with ***
    return preg_match('/_dot_string.txt$/', $var);
}

// https://stackoverflow.com/a/15774702/841052
$path = 'models/';
$files = scandir($path);
$files = array_diff(scandir($path), array('.', '..'));

// https://www.php.net/manual/en/function.array-filter.php
$files = array_filter($files, "hasSuffix");

// reset numbering https://stackoverflow.com/questions/10492839/reset-keys-of-array-elements-in-php/10492870
$files = array_values($files);
# echo print_r($files);

// usage http://localhost:8080/deleteModel.php?model=4
// this is only for users w/ access to the editor

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

$modelToDelete = $queries['model'];

// echo print_r($files);
// echo "<br />";
$result = array_search($modelToDelete, $files, true);

if ($result === false) {
    echo "Not found";
} else{
    echo $result;

    if (is_numeric($result)) {
        $fileToDelete = $files[$result];

        $baseDirectory = 'models/';
        if (unlink($baseDirectory . $fileToDelete))
            echo "file deleted.";
    } else {
        echo "not a number.";
    }
}




?>
