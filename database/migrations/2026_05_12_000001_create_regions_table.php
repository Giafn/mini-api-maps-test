<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure PostGIS extension is available before adding geometry columns
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tileset_id')->constrained('tilesets')->cascadeOnDelete();
            $table->enum('level', ['province', 'regency', 'district', 'village']);
            $table->string('code')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        // Add PostGIS geometry column and spatial index (SRID 4326)
        DB::statement('ALTER TABLE regions ADD COLUMN geometry geometry(MULTIPOLYGON,4326)');
        DB::statement('CREATE INDEX regions_geometry_gist ON regions USING GIST (geometry)');
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
