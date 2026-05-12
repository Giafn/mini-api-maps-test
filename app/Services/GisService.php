<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GisService
{
    /**
     * Return points within bounding box (west, south, east, north)
     */
    public function pointsInBbox(float $west, float $south, float $east, float $north, int $limit = 500)
    {
        $sql = 'SELECT id, title, description, latitude, longitude, ST_AsGeoJSON(location) AS geom FROM map_points WHERE ST_Within(location, ST_MakeEnvelope(?, ?, ?, ?, 4326)) LIMIT ?';

        return DB::select($sql, [$west, $south, $east, $north, $limit]);
    }
}
