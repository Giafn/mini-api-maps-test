<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePointRequest;
use App\Models\MapPoint;
use App\Services\GisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends ApiController
{
    public function store(StorePointRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $point = MapPoint::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            // set PostGIS location
            DB::statement('UPDATE map_points SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?', [
                $data['longitude'], $data['latitude'], $point->id,
            ]);

            DB::commit();

            return $this->success(['id' => $point->id], 'Point created', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage(), 500);
        }
    }

    public function index(Request $request, GisService $gis)
    {
        $north = $request->query('north');
        $south = $request->query('south');
        $east = $request->query('east');
        $west = $request->query('west');
        $limit = (int) $request->query('limit', 500);

        if (! is_numeric($north) || ! is_numeric($south) || ! is_numeric($east) || ! is_numeric($west)) {
            return $this->error('Invalid bbox parameters', 422);
        }

        $rows = $gis->pointsInBbox((float) $west, (float) $south, (float) $east, (float) $north, $limit);
        $data = array_map(function ($r) {
            return [
                'id' => $r->id,
                'title' => $r->title,
                'description' => $r->description,
                'latitude' => (float) $r->latitude,
                'longitude' => (float) $r->longitude,
                'geometry' => $r->geom ? json_decode($r->geom, true) : null,
            ];
        }, $rows);

        return $this->success(['points' => $data]);
    }
}
