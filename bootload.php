<?php

if (!function_exists('_require_all')) {
    function _require_all($dir, $depth = 0, $max_scan_depth = 100)
    {
        if ($depth > $max_scan_depth) {
            return false;
        }
        // require all php files
        $scan = glob("$dir/*");
        foreach ($scan as $path) {
            try {
                if (preg_match('/\.php$/', $path)) {
                    require_once $path;
                } elseif (is_dir($path)) {
                    _require_all($path, $depth + 1);
                }
            } catch (Exception $e) {
                var_dump($e);
            }

        }
        return true;
    }
}

include_once __DIR__ . '/src/SCSTypes/Entity.php';
_require_all(__DIR__ . '/src/SCSTypes/EntityContainers');
include_once __DIR__ . '/src/SCSTypes/EntityCache.php';
_require_all(__DIR__ . '/src/SCSTypes/EntityTypes');
