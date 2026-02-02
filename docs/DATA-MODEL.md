# Parks GeoJSON Map – Data Model

This plugin uses existing WordPress CPT, ACF fields, and taxonomies. It does **not** register CPTs, ACF fields, or taxonomies.

## CPT

- **Post type key:** `OC-Park`  
- Registered elsewhere (theme or another plugin).

## ACF Fields (post meta)

| ACF field   | Park model      | ParkInfo property |
|------------|-----------------|-------------------|
| park_name  | info.name       | name              |
| park_address | info.address  | address           |
| park_type  | info.type       | type              |
| park_size  | info.size       | size              |
| park_url   | info.url        | url               |

Plugin also uses:

- **park_global_id** – stored in post meta for upsert matching (GeoJSON import).
- **geometry_geojson** – JSON-encoded ParkGeometry (type + coordinates) for map rendering.

## Taxonomies

- **Amenities** (slug: `amenities`) – term slugs:  
  `park_parking`, `park_restrooms`, `park_picnic_tables`, `park_picnic_shelters`, `park_bbq`, `park_dog_park`, `park_trailheads`, `park_ampitheater`, `park_concessions`
- **Activities** (slug: `activities`) – term slugs:  
  `park_soccer`, `park_baseball`, `park_softball`, `park_basketball`, `park_volleyball`, `park_pickleball`, `park_tennis`, `park_skating`, `park_shuffleboard`, `park_disc`, `park_horseshoe`, `park_playground`, `park_exercise`, `park_pool`, `park_splash`

If your site uses different taxonomy **slugs** (e.g. not `amenities` / `activities`), update the constants in `includes/Repositories/ParkRepository.php` and the taxonomy names in `includes/ParkFactory.php`.

## Domain models

- **Park** – `global_id`, `info` (ParkInfo), `geometry` (ParkGeometry), `amenities` (term slugs), `activities` (term slugs).
- **ParkInfo** – `name`, `address`, `type`, `size`, `url` (maps to ACF).
- **ParkGeometry** – `type`, `coordinates` (GeoJSON-style).

`ParkRepository::upsert()` writes to OC-Park posts and meta; `ParkFactory::from_post()` reads from them.
