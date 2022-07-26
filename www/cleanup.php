<?php

include 'common.inc';

$ids = [];
if (!empty($_REQUEST['tests'])) {
    $ids = $_REQUEST['tests'];
} else {
    $json = file_get_contents('php://input');
    $data = json_decode($json);
    if (!empty($data['tests'])) {
        $ids = $data['tests'];
    }
}
$api_keys = null;
if (!empty($_REQUEST['k']) && strlen($_REQUEST['k'])) {
    $keys_file = __DIR__ . '/settings/keys.ini';
    if (file_exists(__DIR__ . '/settings/common/keys.ini')) {
        $keys_file = __DIR__ . '/settings/common/keys.ini';
    }
    if (file_exists(__DIR__ . '/settings/server/keys.ini')) {
        $keys_file = __DIR__ . '/settings/server/keys.ini';
    }
    $api_keys = parse_ini_file($keys_file, true);
}
if (empty($_REQUEST['k']) || empty($api_keys) || !isset($api_keys[$_REQUEST['k']])) {
    header("HTTP/1.0 403 Invalid key");
    return;
}
$ok = false;
foreach($ids as $id) {
    $testPath = './' . GetTestPath($id);
    if (
        strpos($testPath, 'results') !== false
        && strpos($testPath, '..') === false
        && is_dir($testPath)
    ) {
        // delete the test directory
        delTree($testPath);
        $ok = true;
    }
}

if (!$ok) {
    header("HTTP/1.0 404 Not Found");
}
