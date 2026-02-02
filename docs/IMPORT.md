# Parks GeoJSON Import

## Admin upload flow

- **Location:** Settings → Parks GeoJSON Import (requires `manage_options`).
- **Form:** Upload a GeoJSON file (`.geojson` or `.json`) and click **Import GeoJSON**.
- **Security:** Nonce and capability checks are performed; only whitelisted taxonomy term slugs (see TaxonomyConstants) are assigned to posts.
- **Feedback:** After import, a transient message shows how many parks were imported and how many features were skipped (invalid or missing id/geometry).

## Input schema (type safety)

The expected structure is defined in **schemas/parks-geojson-input.json** (JSON Schema). Before import, the file is validated by **GeoJsonInputValidator**: root must be `type: "FeatureCollection"` with a `features` array; each feature must have `type: "Feature"`, `geometry` (with `type` and `coordinates`), and at least one of `id` or `properties.GlobalID`. Validation errors are shown on the import page.

## Expected GeoJSON structure

The file must be a **GeoJSON FeatureCollection** with a `features` array. Each feature represents one park.

### Feature requirements

- **`id`** (optional at top level) or **`properties.GlobalID`** – Unique identifier for the park. Required for import; features without a valid id fail validation or are skipped.
- **`geometry`** – GeoJSON geometry object with `type` (e.g. `Polygon`) and `coordinates`. Required; features without valid geometry fail validation.
- **`properties`** – Object used to fill park info and taxonomy terms. Only term slugs that exist in **TaxonomyConstants** (Amenities / Activities) are assigned.

### Property keys (sample data names first)

The importer maps the first non-empty value for each set of keys (case-sensitive). Sample data uses `NAME`, `FULLADDR`, `TYPE`, `PARKAREA`, `PARKURL`, and `GlobalID`.

| Purpose   | Property keys (try in order) |
|----------|------------------------------|
| Name     | `NAME`, `name`, `PARK_NAME`, `Name` |
| Address  | `FULLADDR`, `address`, `PARK_ADDRESS`, `Address` |
| Type     | `TYPE`, `type`, `PARK_TYPE`, `Type` |
| Size     | `PARKAREA`, `size`, `PARK_SIZE`, `Size` (number or string) |
| URL      | `PARKURL`, `url`, `PARK_URL`, `Url`, `external_url` |
| Amenities| `amenities` (comma-separated slugs or array); or derived from `PARKING`, `RESTROOM`, `PICNICTABLES`, `PICNICSHELTER`, `BBQ`, `DOGPARK`, `TRAILHEADS`, `AMPSTA`, `CONCESSION` (Yes or count > 0) |
| Activities | `activities` (comma-separated slugs or array); or derived from `SOCCFOOT`, `BASEBALL`, `SOFTBALL`, `BASKETBALL`, `VOLLEYBALL`, `PICKLEBALL`, `TENNIS`, `SKATEFAC`, `SHUFFLEBOARD`, `DISCGOLF`, `HORSESHOE`, `PLAYGROUND`, `FITNESSZONE`, `SWIMMINGPOOL`, `SPLASHPADS` (1 or Yes) |

Missing optional fields default to empty string. Amenities and activities are filtered to the allowed term slugs defined in **includes/TaxonomyConstants.php**; any slug not in those lists is ignored.

### Allowed taxonomy term slugs

- **Amenities:** `park_parking`, `park_restrooms`, `park_picnic_tables`, `park_picnic_shelters`, `park_bbq`, `park_dog_park`, `park_trailheads`, `park_ampitheater`, `park_concessions`
- **Activities:** `park_soccer`, `park_baseball`, `park_softball`, `park_basketball`, `park_volleyball`, `park_pickleball`, `park_tennis`, `park_skating`, `park_shuffleboard`, `park_disc`, `park_horseshoe`, `park_playground`, `park_exercise`, `park_pool`, `park_splash`

Validation is applied in **GeoJsonToParkMapper** (filter to allowed lists) and again in **ParkRepository::upsert()** before `wp_set_object_terms()`.
