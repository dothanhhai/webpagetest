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
    'utilization' => null,
    'longTasks' => null,
    'mainThread' => [
        "miliseconds" => 0,
        "data" => null
    ]
];

// Load the long tasks list
if (gz_is_file($localPaths->longTasksFile())) {
    $long_tasks = json_decode(gz_file_get_contents($localPaths->interactiveFile()), true);;
    if (isset($long_tasks) && is_array($long_tasks)) {
        $data['longTasks']['data'] = $long_tasks;
    }
}

$max_bw = !empty($_REQUEST['max_bw']) ? $_REQUEST['max_bw'] : 0;
// Load CPU and BW
$data['utilization'] = LoadPerfData($localPaths->utilizationFile(), true, true, false, $max_bw);

// Calculate the timeline-based CPU times
$cpu_slices = DevToolsGetCPUSlicesForStep($localPaths);
$reduceBlock = 10000 / $cpu_slices['slice_usecs'];
$cpuTypes = cpuTypes();

if ($cpu_slices['slices'][$cpu_slices['main_thread']]) {
    $mainThread = $cpu_slices['slices'][$cpu_slices['main_thread']];
    $data['mainThread']['miliseconds'] = round($cpu_slices['total_usecs'] / 1000);
    foreach ($mainThread as $key => $items) {
        foreach ($items as $k => $v) {
            if ($v > 0) {
                $timeBlock = floor($k / $reduceBlock);
                $cpuType = $key;
                if (!isset($cpuTypes[$key])) {
                    $cpuType = 'Other';
                }
                if (empty($data['mainThread']['data']["$timeBlock"][$cpuType])) {
                    $data['mainThread']['data']["$timeBlock"][$cpuType] = 0;
                }
                $data['mainThread']['data']["$timeBlock"][$cpuType] += $v;
            }
        }
    }
    foreach ($data['mainThread']['data'] as $timeBlock => $items) {
        foreach ($items as $k => $v) {
            $data['mainThread']['data'][$timeBlock][$k] = round($data['mainThread']['data'][$timeBlock][$k] / ($reduceBlock == 1 ? 10 : $reduceBlock));
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);

function cpuTypes()
{
    $cpuTypes = array();

    $cpuTypes['ParseHTML'] = true;
    $cpuTypes['ResourceReceivedData'] = true;
    $cpuTypes['ResourceSendRequest'] = true;
    $cpuTypes['ResourceReceivedResponse'] = true;
    $cpuTypes['ResourceReceiveResponse'] = true;
    $cpuTypes['ResourceFinish'] = true;
    $cpuTypes['CommitLoad'] = true;

    $cpuTypes['Layout'] = true;
    $cpuTypes['RecalculateStyles'] = true;
    $cpuTypes['ParseAuthorStyleSheet'] = true;
    $cpuTypes['ScheduleStyleRecalculation'] = true;
    $cpuTypes['InvalidateLayout'] = true;
    $cpuTypes['UpdateLayoutTree'] = true;

    $cpuTypes['Paint'] = true;
    $cpuTypes['PaintImage'] = true;
    $cpuTypes['PaintSetup'] = true;
    $cpuTypes['CompositeLayers'] = true;
    $cpuTypes['DecodeImage'] = true;
    $cpuTypes['Decode Image'] = true;
    $cpuTypes['ImageDecodeTask'] = true;
    $cpuTypes['Rasterize'] = true;
    $cpuTypes['GPUTask'] = true;
    $cpuTypes['SetLayerTreeId'] = true;
    $cpuTypes['layerId'] = true;
    $cpuTypes['UpdateLayer'] = true;
    $cpuTypes['UpdateLayerTree'] = true;
    $cpuTypes['Draw LazyPixelRef'] = true;
    $cpuTypes['Decode LazyPixelRef'] = true;

    $cpuTypes['EvaluateScript'] = true;
    $cpuTypes['EventDispatch'] = true;
    $cpuTypes['FunctionCall'] = true;
    $cpuTypes['GCEvent'] = true;
    $cpuTypes['TimerInstall'] = true;
    $cpuTypes['TimerFire'] = true;
    $cpuTypes['TimerRemove'] = true;
    $cpuTypes['XHRLoad'] = true;
    $cpuTypes['XHRReadyStateChange'] = true;
    $cpuTypes['v8.compile'] = true;
    $cpuTypes['MinorGC'] = true;
    $cpuTypes['MajorGC'] = true;
    $cpuTypes['FireAnimationFrame'] = true;
    $cpuTypes['ThreadState::completeSweep'] = true;
    $cpuTypes['Heap::collectGarbage'] = true;
    $cpuTypes['ThreadState::performIdleLazySweep'] = true;

    $cpuTypes['Other'] = true;
    $cpuTypes['Program'] = true;

    return $cpuTypes;
}
