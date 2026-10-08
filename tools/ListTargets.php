<?php
$d = json_decode(file_get_contents(dirname(__DIR__) . '/resources/mappings/master.json'), true);
$targets = [];
foreach ($d['rules'] as $r) {
    if ($r['kind'] !== 'function') {
        continue;
    }
    $t = $r['target'] ?? '';
    if (str_contains($t, 'Core\\') || in_array($r['source'], ['wp_die', 'wp_redirect', 'is_multisite'], true)) {
        $targets[$r['source']] = $t
            . ' [mode=' . $r['mode'] . ']'
            . (!empty($r['throws']) ? ' throws' : '')
            . (!empty($r['echo']) ? ' echo' : '')
            . (isset($r['wrap']) ? ' wrap=' . $r['wrap'] : '')
            . (isset($r['group']) ? ' g=' . $r['group'] : '');
    }
}
ksort($targets);
foreach ($targets as $k => $v) {
    echo $k . ' => ' . $v . PHP_EOL;
}