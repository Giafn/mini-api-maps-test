<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPmtilesRequest;
use App\Models\Tileset;
use App\Services\PmtilesGeneratorService;
use Exception;
use Illuminate\Support\Str;

class PmtilesController extends ApiController
{
    public function index()
    {
        $list = Tileset::orderByDesc('created_at')->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'filename' => $t->filename,
                'url' => '/storage/pmtiles/'.$t->filename,
                'created_at' => $t->created_at,
            ];
        });

        return $this->success($list);
    }

    public function show(Tileset $tileset)
    {
        return $this->success([
            'id' => $tileset->id,
            'name' => $tileset->name,
            'filename' => $tileset->filename,
            'url' => '/storage/pmtiles/'.$tileset->filename,
            'status' => $tileset->status,
            'metadata' => $tileset->metadata,
            'created_at' => $tileset->created_at,
        ]);
    }

    public function import(ImportPmtilesRequest $request, PmtilesGeneratorService $generator)
    {
        $name = $request->input('name');
        $files = $request->file('files');
        $tmpPaths = [];
        foreach ($files as $file) {
            // move to temp to allow external tools to read
            $tmp = sys_get_temp_dir().'/pmtiles_'.Str::orderedUuid().'.geojson';
            $file->move(dirname($tmp), basename($tmp));
            $tmpPaths[] = $tmp;
        }

        try {
            $tileset = $generator->import($name, $tmpPaths, count($tmpPaths));

            return $this->success(['id' => $tileset->id], 'Import started', 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
