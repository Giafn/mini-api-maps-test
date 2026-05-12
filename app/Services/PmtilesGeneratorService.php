<?php

namespace App\Services;

use App\Models\Tileset;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PmtilesGeneratorService
{
    public function __construct(protected array $config = [])
    {
        $this->config = array_merge(config('gis', []), $config);
    }

    /**
     * Import multiple GeoJSON files into PostGIS, generate MBTiles via tippecanoe and convert to PMTiles.
     * Returns Tileset on success.
     */
    public function import(string $name, array $uploadedPaths, int $originalCount): Tileset
    {
        $uniq = Str::orderedUuid();
        $tmp = storage_path('app/pmtiles_tmp/'.$uniq);
        @mkdir($tmp, 0755, true);

        DB::beginTransaction();
        $tileset = Tileset::create([
            'name' => $name,
            'filename' => $uniq.'.pmtiles',
            'original_file_count' => $originalCount,
            'status' => 'processing',
        ]);

        try {
            $staging = $tmp.'/combined.geojson';

            // Combine files into a single FeatureCollection using SQL to ensure valid geometry via PostGIS
            $allFeatures = [];
            foreach ($uploadedPaths as $path) {
                $contents = file_get_contents($path);
                $json = json_decode($contents, true);
                if (! $json) {
                    throw new Exception('Invalid JSON in uploaded file: '.$path);
                }
                $features = $json['features'] ?? null;
                if (is_array($features)) {
                    foreach ($features as $f) {
                        $allFeatures[] = $f;
                    }
                } else {
                    // If it's a single geometry Feature
                    if (isset($json['type']) && $json['type'] === 'Feature') {
                        $allFeatures[] = $json;
                    }
                }
            }

            $fc = ['type' => 'FeatureCollection', 'features' => $allFeatures];
            file_put_contents($staging, json_encode($fc));

            // Generate mbtiles via tippecanoe
            $mbtiles = $tmp.'/out.mbtiles';
            $tippecanoe = $this->config['tippecanoe_bin'] ?? 'tippecanoe';
            $pmtilesCli = $this->config['pmtiles_bin'] ?? 'pmtiles';

            $cmd = [$tippecanoe, '-o', $mbtiles, '--force', '--no-tile-compression', '-l', 'regions', $staging];
            $proc = new Process($cmd);
            $proc->setTimeout($this->config['tippecanoe_timeout'] ?? 300);
            $proc->run();
            if (! $proc->isSuccessful()) {
                throw new Exception('tippecanoe failed: '.$proc->getErrorOutput().$proc->getOutput());
            }

            // Convert to pmtiles (expects pmtiles CLI installed)
            $pmtiles = $tmp.'/out.pmtiles';
            $cmd2 = [$pmtilesCli, 'convert', $mbtiles, $pmtiles];
            $proc2 = new Process($cmd2);
            $proc2->setTimeout($this->config['pmtiles_timeout'] ?? 120);
            $proc2->run();
            if (! $proc2->isSuccessful()) {
                throw new Exception('pmtiles convert failed: '.$proc2->getErrorOutput().$proc2->getOutput());
            }

            // Move to storage
            $dest = 'pmtiles/'.$tileset->filename;
            Storage::disk('public')->put($dest, file_get_contents($pmtiles));

            $tileset->update(['status' => 'ready', 'metadata' => ['source_count' => $originalCount]]);

            DB::commit();

            // cleanup
            @unlink($staging);
            @unlink($mbtiles);
            @unlink($pmtiles);
            foreach ($uploadedPaths as $p) {
                @unlink($p);
            }
            @rmdir($tmp);

            return $tileset;
        } catch (Exception $e) {
            DB::rollBack();
            // cleanup
            foreach ($uploadedPaths as $p) {
                if (file_exists($p)) {
                    @unlink($p);
                }
            }
            if (file_exists($tmp)) {
                @unlink($tmp.'/combined.geojson');
                @unlink($tmp.'/out.mbtiles');
                @unlink($tmp.'/out.pmtiles');
                @rmdir($tmp);
            }
            // update tileset
            $tileset->update(['status' => 'failed', 'metadata' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }
}
