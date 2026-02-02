# Parks GeoJSON Map

WordPress plugin that imports park GeoJSON polygons and renders a map on single-park pages via the `[parks_map]` shortcode.

## Requirements

- WordPress with the **OC-Park** custom post type already registered
- **ACF fields** for parks: `park_name`, `park_address`, `park_type`, `park_size`, `park_url`
- **Taxonomies** Amenities and Activities (with term slugs matching `includes/TaxonomyConstants.php`)
- **Google Maps JavaScript API** key (for map rendering)

This plugin does **not** register CPTs, ACF fields, or taxonomies; it uses your existing setup.

## Installation

1. Copy the plugin folder into `wp-content/plugins/` (e.g. `wp-content/plugins/parks-geojson-map/`).
2. Activate **Parks GeoJSON Map** in the WordPress admin.
3. Go to **Settings → Parks GeoJSON Import**, enter your Google Maps API key, and click **Save map settings**.

## Usage

### Import GeoJSON

1. Go to **Settings → Parks GeoJSON Import**.
2. Upload a GeoJSON **FeatureCollection** file (`.geojson` or `.json`).
3. Click **Import GeoJSON**.

The file is validated against the input schema; each feature must have `geometry` and a unique `id` or `properties.GlobalID`. Park info and amenities/activities are mapped from properties (see [docs/IMPORT.md](docs/IMPORT.md)).

### Map on single-park page

Add the shortcode **`[parks_map]`** to the template or content of a single OC-Park post. The shortcode outputs a map div, draws the park polygon from stored geometry, and shows an InfoWindow (tooltip) on click with park name, address, type, size, and link.

### Map settings

- **Google Maps API key:** Set under **Settings → Parks GeoJSON Import** → “Google Maps API key” → **Save map settings**. If empty, the shortcode shows a notice instead of the map.

## Project structure

```
parks-geojson-map/
├── assets/js/parks-map.js    # Google Maps: polygon + click tooltip
├── docs/
│   ├── DATA-MODEL.md         # CPT, ACF, taxonomies, domain models
│   ├── IMPORT.md             # GeoJSON import flow and property mapping
│   └── MAP.md                # Map rendering, API key, shortcode
├── includes/
│   ├── admin-import.php      # Admin screen: import + map settings
│   ├── GeoJsonInputValidator.php
│   ├── GeoJsonToParkMapper.php
│   ├── maps-render.php       # [parks_map] shortcode
│   ├── ParkFactory.php
│   ├── TaxonomyConstants.php
│   ├── Models/               # Park, ParkInfo, ParkGeometry
│   └── Repositories/         # ParkRepository
├── schemas/
│   └── parks-geojson-input.json   # JSON Schema for import input
├── parks-geojson-map.php     # Plugin bootstrap
└── README.md
```

## Documentation

- **[docs/DATA-MODEL.md](docs/DATA-MODEL.md)** — Data model, ACF fields, taxonomies, and domain objects.
- **[docs/IMPORT.md](docs/IMPORT.md)** — GeoJSON import, input schema, validation, and property keys (including sample data names).
- **[docs/MAP.md](docs/MAP.md)** — Map rendering, API key, shortcode usage, and tooltip content.
- **[schemas/parks-geojson-input.json](schemas/parks-geojson-input.json)** — JSON Schema for the expected GeoJSON input.

## Scripts

- **Zip project:** Double-click **Zip-Project.bat** (or run `.\zip-project.ps1` in PowerShell) to create `parks-geojson-map.zip` in the project root. The script excludes `.git`, `node_modules`, and existing `.zip` files.

## Version

0.1
