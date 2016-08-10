<?php

require_once __DIR__ . '/shared.php';

$config = require_once __DIR__ . '/fatsecret-config.php';

$importedColumns = array_filter($config['import']);
$importedKeys = array_intersect_key($config['keys'], $importedColumns);

// Ask data-tracker what is the last data point we've saved
$lastDatapoints = [];
foreach ($importedKeys as $key) {
    $lastDatapoint = getDataTracker('logs/' . $key . '?order=desc&limit=1&keyed=1');
    $lastDatapoints[$key] = $lastDatapoint ? $lastDatapoint[0][0] : null;
}
// If there are any point, the first one will do, otherwise we will accept anything
$lastDatapoint = empty($lastDatapoints) ? 0 : reset($lastDatapoints);

// Read all .eml files in the given folder
$files = glob($config['directory'] . '/*.eml');
$dataLineRegex = '/^"(?<date>\S+, \S+ \d+, \d+)",(?<values>[\d\.,]+)/';
$data = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_split('/\r\n|\r|\n/', $content);

    foreach ($content as $line) {
        // Find a day summary line
        if ( ! preg_match($dataLineRegex, $line, $matches)) {
            continue;
        }

        $date = strtotime($matches['date']);

        // Skip data if it occurred before the last datapoint
        if ($date <= $lastDatapoint) {
            continue;
        }

        $values = explode(',', $matches['values']);
        $values = array_map('floatval', $values);
        $values = array_combine(array_keys($config['keys']), $values);
        // Remove keys we do not want to import
        $values = array_intersect_key($values, $importedColumns);
        // Remap data from column names to keys
        $values = array_combine($importedKeys, $values);
        $data[] = ['_timestamp' => $date] + $values;
    }
}

// Insert datapoints into the data tracker
$out = postDataTracker('logs/bulk', $data);
var_dump($out);
