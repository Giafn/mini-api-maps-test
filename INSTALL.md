PMTiles + PostGIS Proof of Concept
=================================

Requirements
- PHP 8.3
- Laravel 12
- PostgreSQL with PostGIS enabled
- tippecanoe (https://github.com/mapbox/tippecanoe)
- pmtiles CLI (https://github.com/mapbox/pmtiles or equivalent)

Environment
- Set DB_CONNECTION=pgsql and usual Postgres env vars in `.env`
- Optionally set paths for binaries:
  - TIPPECANOE_BIN (/usr/local/bin/tippecanoe)
  - PMTILES_BIN (/usr/local/bin/pmtiles)

Setup

1. Install PostGIS on PostgreSQL and enable extension:

   psql -d your_db -c "CREATE EXTENSION IF NOT EXISTS postgis;"

2. Install tippecanoe and pmtiles on the system. Both must be callable by the web user.

3. Storage link for public access to PMTiles:

   php artisan storage:link

4. Run migrations:

   php artisan migrate

Usage

Import many GeoJSON files in a single request (multipart/form-data):

curl -X POST "$(php -r 'echo rtrim((getenv("APP_URL")?:"http://localhost"),"/");')/api/pmtiles/import" \
  -F "name=jateng_tileset" \
  -F "files[]=@/path/to/first.geojson" \
  -F "files[]=@/path/to/second.geojson"

List tilesets:

  GET /api/pmtiles

Tileset file served via public storage: `/storage/pmtiles/{filename}`

Add a point:

curl -X POST "$(php -r 'echo rtrim((getenv("APP_URL")?:"http://localhost"),"/");')/api/points" -H "Content-Type: application/json" -d '{"title":"Marker","description":"Long text","latitude":-6.9,"longitude":107.6}'

Query points by viewport (bbox):

GET /api/points?north=...&south=...&east=...&west=...

Notes
- This POC uses PostGIS geometry columns directly and creates GiST spatial indexes.
- The importer combines GeoJSON files into one FeatureCollection, runs `tippecanoe` to create an MBTiles, then converts to PMTiles using the `pmtiles` CLI. Ensure binaries are installed and accessible.
- For large imports consider using `ogr2ogr` (GDAL) to stream data into PostGIS instead of PHP parsing. The code is written to be replaced with an `ogr2ogr` path if available.

Scramble API documentation (Scramble - https://scramble.dedoc.co/)
------------------------------------------------------------

To use Scramble for API documentation generation and UI:

1. Install via composer:

```bash
composer require dedoc/scramble
```

2. (Optional) Publish the config to customize behaviour:

```bash
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config"
```

3. After installation the docs UI will be available at `/docs` and the OpenAPI JSON
  at `/docs/openapi.json`. You can export the OpenAPI spec to a file with:

```bash
php artisan scramble:export --path=public/api.json
```

4. If you want the docs to be publicly viewable in non-local environments, adjust
  `config/scramble.php` middleware (the default restricts access to `local` env).

Notes:
- Scramble will generate OpenAPI 3.1.0 from your routes and controllers.
- The project already includes a basic `config/scramble.php` and an `AppServiceProvider`
  configuration that exposes the docs at `/docs` and `/docs/openapi.json` when the
  package is installed.

