<?php

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

require_once(INCLUDES_PATH . '/include/TestPaths.php');
require_once(INCLUDES_PATH . '/draw.inc');

$localPaths = new TestPaths($testPath, $run, $cached);

$data = [
    'utilization' => [],
    'longTasks' => [],
    'mainThread' => [
        "miliseconds" => 0,
        "data" => []
    ]
];

// Load the long tasks list
if (gz_is_file($localPaths->longTasksFile())) {
    $long_tasks = json_decode(gz_file_get_contents($localPaths->longTasksFile()), true);
    if (isset($long_tasks) && is_array($long_tasks)) {
        $data['longTasks']['data'] = $long_tasks;
    }
}

$max_bw = !empty($_REQUEST['max_bw']) ? $_REQUEST['max_bw'] : 0;
// Load CPU and BW
$data['utilization'] = LoadPerfData($localPaths->utilizationFile(), true, true, false, $max_bw);

// Calculate the timeline-based CPU times
$cpu_slices = DevToolsGetCPUSlicesForStep($localPaths);

if ($cpu_slices['slices'][$cpu_slices['main_thread']]) {
    $mainThread = $cpu_slices['slices'][$cpu_slices['main_thread']];
    $data['mainThread']['miliseconds'] = round($cpu_slices['total_usecs'] / 1000);
    foreach ($mainThread as $key => $items) {
        $data['mainThread']['data'][$key] = max10Items($items);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);


function max10Items($array)
{
    $chunks = array_chunk($array, 10);
    $maxValues = [];
    foreach ($chunks as $key => $chunk) {
        $index = $key * 10;
        $max = 0;
        $maxValue = ["$index" => 0];
        foreach ($chunk as $k => $v) {
            if ($max < $v) {
                $ml = $index + $k;
                $max = $v;
                $maxValue = ["$ml" => $v];
            }
        }
        if ($max > 0) {
            $maxValues[] = $maxValue;
        }
    }
    return $maxValues;
}
