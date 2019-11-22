<?php
ini_set("display_errors", "On");
if (file_exists(dirname(__FILE__) . "/orderFile2.json")) {
    echo "We have the file\n";
}

$data = file_get_contents(dirname(__FILE__) . "/orderFile2.json");

$orders = json_decode($data, true);
//print_r(json_last_error());
//print_r("\n");
//print_r($orders);

$bigArr = array();

foreach($orders as $o) {
    $bigArr = array_replace_recursive($bigArr, $o);
}

print_r($bigArr);

?>