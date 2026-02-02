# Parks Map (Google Maps)

## How the map is rendered

- The **shortcode** `[parks_map]` outputs a div `#parks-map` and sets `window.PARK_DATA` (current park: geometry, info, amenities, activities).
- The **Google Maps JavaScript API** is loaded with your API key, then **assets/js/parks-map.js** runs.
- The script uses the **Data layer** (`google.maps.Data`): it builds a GeoJSON Feature from `PARK_DATA.geometry` (type + coordinates) and adds it with `data.addGeoJson()`, so the park polygon is drawn on the map.
- The map is fitted to the polygon bounds via `Data.forEach` and `LatLngBounds`.
- **Tooltip:** A click listener on the Data layer opens an **InfoWindow** at the click position. The content is built from `PARK_DATA.info`: park name, address, type, size, and a link if URL is set.

## Google Maps API key

- **Where to set:** Settings → Parks GeoJSON Import → “Google Maps API key” field → **Save map settings**.
- **Stored in:** WordPress option `parks_google_maps_api_key`.
- **If empty:** The shortcode returns a notice asking you to add a key; the map script is not enqueued.
- **Alternative:** You can set the key via code (e.g. `update_option('parks_google_maps_api_key', 'YOUR_KEY')`) if you prefer not to use the admin field.

## Shortcode usage

- Use **`[parks_map]`** on a single OC-Park post (or any template where `get_the_ID()` returns an OC-Park post). The shortcode expects the current post to be a park; otherwise it outputs “No park found.”
- It outputs the map container (100% width, 500px height), injects `window.PARK_DATA`, and enqueues the Google Maps script and **parks-map.js** so the polygon and click tooltip work.

## Tooltip content

The InfoWindow shows: park name (bold), address, type, size, and a “Link” anchor if `info.url` is set. All values come from `PARK_DATA.info` (ACF fields: park_name, park_address, park_type, park_size, park_url).
