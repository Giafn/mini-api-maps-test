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

        Schema::create('map_points', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE map_points ADD COLUMN location geometry(Point,4326)');
        DB::statement('CREATE INDEX map_points_location_gist ON map_points USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('map_points');
    }
};
