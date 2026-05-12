<?php

return [
    // Path to tippecanoe binary
    'tippecanoe_bin' => env('TIPPECANOE_BIN', '/usr/local/bin/tippecanoe'),
    'pmtiles_bin' => env('PMTILES_BIN', '/usr/local/bin/pmtiles'),
    'tippecanoe_timeout' => env('TIPPECANOE_TIMEOUT', 300),
    'pmtiles_timeout' => env('PMTILES_TIMEOUT', 120),
    'tmp_path' => storage_path('app/pmtiles_tmp'),
];
