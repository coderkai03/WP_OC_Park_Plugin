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
 *   - amenities: comma-separated slugs or array; or derived from PARKING, RESTROOM, PICNICTABLES, etc. (Yes/count > 0)
 *   - activities: comma-separated slugs or array; or derived from SOCCFOOT, BASEBALL, BASKETBALL, etc. (1/Yes)
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
      self::get_string($props, ['NAME', 'name', 'PARK_NAME', 'Name']),
      self::get_string($props, ['FULLADDR', 'address', 'PARK_ADDRESS', 'Address']),
      self::get_string($props, ['TYPE', 'type', 'PARK_TYPE', 'Type']),
      self::get_string($props, ['PARKAREA', 'size', 'PARK_SIZE', 'Size']),
      self::get_string($props, ['PARKURL', 'url', 'PARK_URL', 'Url', 'external_url']),
    );

    // Set the geometry
    $park->geometry = new ParkGeometry(
      $geometry['type'],
      $geometry['coordinates']
    );

    // Parse amenities: explicit slugs (amenities key) or derive from sample keys (PARKING, RESTROOM, etc.)
    $amenity_slugs = array_merge(
      self::parse_term_slugs($props, 'amenities'),
      self::parse_amenities_from_sample_properties($props)
    );
    $park->amenities = TaxonomyConstants::filter_allowed_amenities($amenity_slugs);

    // Parse activities: explicit slugs (activities key) or derive from sample keys (SOCCFOOT, BASEBALL, etc.)
    $activity_slugs = array_merge(
      self::parse_term_slugs($props, 'activities'),
      self::parse_activities_from_sample_properties($props)
    );
    $park->activities = TaxonomyConstants::filter_allowed_activities($activity_slugs);

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
   * Get first non-empty string from props using given keys (case-sensitive).
   */
  private static function get_string(array $props, array $keys): string {
    // Check if the properties have a key
    foreach ($keys as $key) {
      if (isset($props[ $key ]) && (string) $props[ $key ] !== '') {
        return (string) $props[ $key ];
      }
    }
    return '';
  }

  /**
   * Parse amenities or activities from properties: comma-separated string or array of slugs.
   *
   * @param array  $props feature properties
   * @param string $key   property key (e.g. 'amenities' or 'activities')
   * @return string[] list of trimmed slugs
   */
  private static function parse_term_slugs(array $props, string $key): array {
    $raw = $props[ $key ] ?? null;
    if (is_array($raw)) {
      return array_map('trim', array_map('strval', $raw));
    }
    if (is_string($raw) && $raw !== '') {
      return array_filter(array_map('trim', explode(',', $raw)));
    }
    return [];
  }

  /**
   * Derive amenity term slugs from sample property keys (PARKING, RESTROOM, PICNICTABLES, etc.).
   * Yes/No or count; only allowed slugs are returned by filter_allowed_amenities in from_feature.
   *
   * @param array $props feature properties
   * @return string[] list of term slugs
   */
  private static function parse_amenities_from_sample_properties(array $props): array {
    $slugs = [];
    $amenity_map = [
      'PARKING'       => 'park_parking',
      'RESTROOM'      => 'park_restrooms',
      'PICNICTABLES'  => 'park_picnic_tables',
      'PICNICSHELTER' => 'park_picnic_shelters',
      'BBQ'           => 'park_bbq',
      'DOGPARK'       => 'park_dog_park',
      'TRAILHEADS'    => 'park_trailheads',
      'AMPSTA'        => 'park_ampitheater',
      'CONCESSION'    => 'park_concessions',
    ];
    foreach ($amenity_map as $key => $slug) {
      $val = $props[ $key ] ?? null;
      if (self::is_yes_or_positive($val)) {
        $slugs[] = $slug;
      }
    }
    return $slugs;
  }

  /**
   * Derive activity term slugs from sample property keys (SOCCFOOT, BASEBALL, etc.).
   * 1 / "1" / "Yes" → add slug; only allowed slugs are returned by filter in from_feature.
   *
   * @param array $props feature properties
   * @return string[] list of term slugs
   */
  private static function parse_activities_from_sample_properties(array $props): array {
    $slugs = [];
    $activity_map = [
      'SOCCFOOT'     => 'park_soccer',
      'BASEBALL'     => 'park_baseball',
      'SOFTBALL'     => 'park_softball',
      'BASKETBALL'   => 'park_basketball',
      'VOLLEYBALL'   => 'park_volleyball',
      'PICKLEBALL'   => 'park_pickleball',
      'TENNIS'       => 'park_tennis',
      'SKATEFAC'     => 'park_skating',
      'SHUFFLEBOARD' => 'park_shuffleboard',
      'DISCGOLF'     => 'park_disc',
      'HORSESHOE'    => 'park_horseshoe',
      'PLAYGROUND'   => 'park_playground',
      'FITNESSZONE'  => 'park_exercise',
      'SWIMMINGPOOL' => 'park_pool',
      'SPLASHPADS'   => 'park_splash',
    ];
    foreach ($activity_map as $key => $slug) {
      $val = $props[ $key ] ?? null;
      if (self::is_yes_or_positive($val)) {
        $slugs[] = $slug;
      }
    }
    return $slugs;
  }

  /**
   * True if value indicates "yes" or positive (e.g. "Yes", 1, "1", count > 0).
   */
  private static function is_yes_or_positive($val): bool {
    if ($val === null) {
      return false;
    }
    if (is_numeric($val)) {
      return (float) $val > 0;
    }
    $s = strtolower(trim((string) $val));
    return $s === 'yes' || $s === '1' || $s === 'true';
  }
}
