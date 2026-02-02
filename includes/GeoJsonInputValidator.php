<?php
/**
 * Validates decoded GeoJSON against the expected input schema for parks import.
 * Ensures type safety: FeatureCollection with features array, each feature has
 * type, geometry (type + coordinates), and id or properties.GlobalID.
 *
 * @see schemas/parks-geojson-input.json for the full input schema.
 */

defined('ABSPATH') || exit;

class GeoJsonInputValidator {

  /**
   * Validate decoded GeoJSON data. Returns list of error messages; empty array means valid.
   *
   * @param array $data Decoded JSON (expect FeatureCollection).
   * @return string[] List of error messages (empty if valid).
   */
  public static function validate(array $data): array {
    $errors = [];

    if (! isset($data['type']) || $data['type'] !== 'FeatureCollection') {
      $errors[] = 'Root must have "type": "FeatureCollection".';
    }

    if (! isset($data['features'])) {
      $errors[] = 'Root must have "features" array.';
      return $errors;
    }

    if (! is_array($data['features'])) {
      $errors[] = '"features" must be an array.';
      return $errors;
    }

    foreach ($data['features'] as $index => $feature) {
      if (! is_array($feature)) {
        $errors[] = sprintf('Feature at index %d must be an object.', $index);
        continue;
      }
      $feature_errors = self::validate_feature($feature, $index);
      $errors = array_merge($errors, $feature_errors);
    }

    return $errors;
  }

  /**
   * Validate a single feature. Returns list of error messages for this feature.
   *
   * @param array $feature Decoded feature object.
   * @param int   $index   Feature index (for error messages).
   * @return string[]
   */
  public static function validate_feature(array $feature, int $index = 0): array {
    $errors = [];
    $prefix = sprintf('Feature[%d]: ', $index);

    if (! isset($feature['type']) || $feature['type'] !== 'Feature') {
      $errors[] = $prefix . 'must have "type": "Feature".';
    }

    // At least one of feature.id or properties.GlobalID required for upsert
    $has_id = isset($feature['id']) && $feature['id'] !== '' && $feature['id'] !== null;
    $props = $feature['properties'] ?? [];
    $has_global_id = isset($props['GlobalID']) && $props['GlobalID'] !== '' && $props['GlobalID'] !== null;
    if (! $has_id && ! $has_global_id) {
      $errors[] = $prefix . 'must have "id" or "properties.GlobalID" for upsert.';
    }

    if (! isset($feature['geometry'])) {
      $errors[] = $prefix . 'must have "geometry" object.';
      return $errors;
    }

    $geometry = $feature['geometry'];
    if (! is_array($geometry)) {
      $errors[] = $prefix . '"geometry" must be an object.';
      return $errors;
    }

    if (! isset($geometry['type']) || ! is_string($geometry['type'])) {
      $errors[] = $prefix . '"geometry" must have "type" (string).';
    }

    if (! isset($geometry['coordinates'])) {
      $errors[] = $prefix . '"geometry" must have "coordinates".';
    } elseif (! is_array($geometry['coordinates'])) {
      $errors[] = $prefix . '"geometry.coordinates" must be an array.';
    }

    return $errors;
  }
}
