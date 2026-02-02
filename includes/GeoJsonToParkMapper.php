<?php
/**
 * Maps a single GeoJSON Feature (decoded array) to a Park domain object.
 *
 * Expected feature shape (see schemas/parks-geojson-input.json):
 *   - id (optional): unique identifier, or use properties.GlobalID
 *   - geometry: { type, coordinates } (GeoJSON geometry)
 *   - properties: object with keys below (any missing optional field defaults to empty string)
 *
 * Property keys supported (sample data names first):
 *   - name: NAME, name, PARK_NAME, Name
 *   - address: FULLADDR, address, PARK_ADDRESS, Address
 *   - type: TYPE, type, PARK_TYPE, Type
 *   - size: PARKAREA, size, PARK_SIZE, Size (number or string)
 *   - url: PARKURL, url, PARK_URL, Url, external_url
 *   - amenities: presence of keys matching property fields (see TaxonomyConstants::AMENITIES_MAP)
 *   - activities: presence of keys matching property fields (see TaxonomyConstants::ACTIVITIES_MAP)
 *
 * @see schemas/parks-geojson-input.json
 * @see docs/IMPORT.md
 */

defined('ABSPATH') || exit;

class GeoJsonToParkMapper {

  /**
   * Build a Park from a decoded GeoJSON feature array, or null if invalid.
   *
   * @param array $feature Decoded GeoJSON feature (must have geometry and optionally properties).
   * @return Park|null Park instance or null if feature is invalid (e.g. missing geometry or global_id).
   */
  public static function from_feature(array $feature): ?Park {
    // Check if the feature has a geometry and it's valid
    $geometry = $feature['geometry'] ?? null;
    if (empty($geometry) || ! isset($geometry['type'], $geometry['coordinates'])) {
      return null;
    }

    // Get the global_id from the feature
    $global_id = self::get_global_id($feature);
    if ($global_id === '' || $global_id === null) {
      return null;
    }

    // Get the properties from the feature
    $props = $feature['properties'] ?? [];

    // Create a new Park instance
    $park = new Park();

    // Set the global_id
    $park->global_id = (string) $global_id;

    // Set the info (sample keys: NAME, FULLADDR, TYPE, PARKAREA, PARKURL)
    $park->info = new ParkInfo(
      $props['NAME'] ?? '',
      $props['FULLADDR'] ?? '',
      $props['TYPE'] ?? '',
      $props['PARKAREA'] ?? '',
      $props['PARKURL'] ?? '',
    );

    // Set the geometry
    $park->geometry = new ParkGeometry(
      $geometry['type'],
      $geometry['coordinates']
    );

    // Parse amenities/activities: presence of term-slug keys in properties
    $park->amenities = self::parse_taxonomies_from_properties($props, TaxonomyConstants::AMENITIES_MAP);
    $park->activities = self::parse_taxonomies_from_properties($props, TaxonomyConstants::ACTIVITIES_MAP);

    return $park;
  }

  /**
   * Resolve global_id from feature id or properties.
   */
  private static function get_global_id(array $feature): ?string {
    // Check if the feature has an id
    if (isset($feature['id']) && $feature['id'] !== '' && $feature['id'] !== null) {
      return (string) $feature['id'];
    }
    // Get the properties from the feature
    $props = $feature['properties'] ?? [];
    // Check if the properties have a GlobalID, global_id, globalid, or id
    foreach (['GlobalID', 'global_id', 'globalid', 'id'] as $key) {
      if (isset($props[ $key ]) && $props[ $key ] !== '' && $props[ $key ] !== null) {
        return (string) $props[ $key ];
      }
    }
    return null;
  }

  /**
   * Parse taxonomy term slugs from properties by iterating a [property_field => slug] map.
   *
   * @param array    $props           Feature properties
   * @param array<string,string> $taxonomy_map  Property-field => slug map (e.g. TaxonomyConstants::AMENITIES_MAP)
   * @return string[] List of slugs present in props
   */
  private static function parse_taxonomies_from_properties(array $props, array $taxonomy_map): array {
    $out = [];
    foreach ($taxonomy_map as $prop_field => $slug) {
      if (!array_key_exists($prop_field, $props) || $props[$prop_field] === 'null') {
        continue;
      }
      
      $out[] = (string) $slug;
    }
    return $out;
  }
}
