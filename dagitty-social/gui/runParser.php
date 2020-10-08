<?php

function isLocalhost($whitelist = ['127.0.0.1', '::1']) {
    return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}

$date = date('Y-m-d H:i:s');
// echo $date;
// echo "<br />";
// https://stackoverflow.com/questions/8945879/how-to-get-body-of-a-post-in-php
// we get the data in the body of a POST request from a XMLHttpRequest
$entityBody = file_get_contents('php://input');
// echo $entityBody;
// https://stackoverflow.com/questions/8469767/get-url-query-string-parameters

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);
// echo print_r($queries['name']);

// somewhat sanitize inputs; sensible defaults.
$myName = 'user-form';
$myType = 'calculator';

// decide based on type
if($queries['type'] == 'calculator') {
    // echo "calc!";
    $myType = 'calculator';
}

if($queries['type'] == 'questionnaire') {
    // echo "questionnaire!";
    $myType = 'questionnaire';
}

// somewhat sanitize filename
// https://stackoverflow.com/a/42908256/841052
$myName = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $queries['name'] ) );
// echo $myName;

// write request to file on disk... sanitize?
$filename = "models/" . $myName . "_dot_string.txt";
file_put_contents($filename, $entityBody);

// https://stackoverflow.com/questions/19735250/running-a-python-script-from-php
// https://stackoverflow.com/questions/30419555/file-reading-from-php-using-python-script
// script is in /dot_to_formly but executed in /gui at the moment.
// https://tecadmin.net/python-command-line-arguments/

$environment = "dev";
$command = "";
if( isLocalhost()) {
    $environment = "dev";
    $command = "/usr/local/bin/python3 ../../dot_to_formly_json-python/dot_to_formly_json.py $environment $myType $myName";
} else {
    $environment = "prod";
    $command = "/usr/bin/python3 /usr/local/bin/dot_to_formly_json-python/dot_to_formly_json.py $environment $myType $myName";
}
ob_start();
passthru($command);
$output = ob_get_clean();
echo $output;

?>