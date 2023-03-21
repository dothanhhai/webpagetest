<?php

// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.

declare(strict_types=1);
require_once __DIR__ . '/common.inc';

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

require_once INCLUDES_PATH . '/include/TestInfo.php';
require_once INCLUDES_PATH . '/include/TestResults.php';

$testInfo = TestInfo::fromFiles($testPath);
$testResults = TestResults::fromFiles($testInfo);
$testStepResult = TestStepResult::fromFiles($testInfo, $run, $cached, $step);
$requests = $testStepResult->getRequests();

include INCLUDES_PATH . '/experiments/common.inc';

header('Content-Type: application/json; charset=utf-8');
echo json_encode($assessment);
