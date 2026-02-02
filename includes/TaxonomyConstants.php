<?php
/**
 * Single source of truth for park taxonomies and allowed term slugs.
 * These must match the existing CPT taxonomies registered elsewhere (do not register here).
 *
 * Used by ParkRepository, ParkFactory, and GeoJsonToParkMapper for persistence and validation.
 */

defined('ABSPATH') || exit;

class TaxonomyConstants {

  /** Taxonomy slug for Amenities (e.g. parking, restrooms). */
  public const TAX_AMENITIES = 'park_amenities';

  /** Taxonomy slug for Activities (e.g. soccer, playground). */
  public const TAX_ACTIVITIES = 'park_activities';

  /**
   * Property field → term slug map for Amenities taxonomy.
   * Only these slugs are assigned on import/save.
   *
   * @var array<string,string>
   */
  public const AMENITIES_MAP = [
    'PARKING'        => 'park_parking',
    'RESTROOM'       => 'park_restrooms',
    'PICNICTABLES'   => 'park_picnic_tables',
    'PICNICSHELTER'  => 'park_picnic_shelters',
    'BBQ'            => 'park_bbq',
    'DOGPARK'        => 'park_dog_park',
    'TRAILHEADS'     => 'park_trailheads',
    'AMPITHEATER'    => 'park_ampitheater',
    'CONCESSION'     => 'park_concessions',
  ];

  /**
   * Property field → term slug map for Activities taxonomy.
   * Only these slugs are assigned on import/save.
   *
   * @var array<string,string>
   */
  public const ACTIVITIES_MAP = [
    'SOCCFOOT'      => 'park_soccer',
    'BASEBALL'      => 'park_baseball',
    'SOFTBALL'      => 'park_softball',
    'BASKETBALL'    => 'park_basketball',
    'VOLLEYBALL'    => 'park_volleyball',
    'PICKLEBALL'    => 'park_pickleball',
    'TENNIS'        => 'park_tennis',
    'SKATEFAC'      => 'park_skating',
    'SHUFFLEBOARD'  => 'park_shuffleboard',
    'DISCGOLF'      => 'park_disc',
    'HORSESHOE'     => 'park_horseshoe',
    'PLAYGROUND'    => 'park_playground',
    'FITNESSZONE'   => 'park_exercise',
    'SWIMMINGPOOL'  => 'park_pool',
    'SPLASHPADS'    => 'park_splash',
  ];

  /**
   * Allowed term slugs for Amenities taxonomy (values of AMENITIES_MAP).
   *
   * @var string[]
   */
  public const AMENITIES_SLUGS = [
    'park_parking',
    'park_restrooms',
    'park_picnic_tables',
    'park_picnic_shelters',
    'park_bbq',
    'park_dog_park',
    'park_trailheads',
    'park_ampitheater',
    'park_concessions',
  ];

  /**
   * Allowed term slugs for Activities taxonomy (values of ACTIVITIES_MAP).
   *
   * @var string[]
   */
  public const ACTIVITIES_SLUGS = [
    'park_soccer',
    'park_baseball',
    'park_softball',
    'park_basketball',
    'park_volleyball',
    'park_pickleball',
    'park_tennis',
    'park_skating',
    'park_shuffleboard',
    'park_disc',
    'park_horseshoe',
    'park_playground',
    'park_exercise',
    'park_pool',
    'park_splash',
  ];

  /**
   * Filter an array of slugs to only those allowed for Amenities.
   *
   * @param string[] $slugs
   * @return string[]
   */
  public static function filter_allowed_amenities(array $slugs): array {
    $allowed = array_flip(self::AMENITIES_SLUGS);
    $out = [];
    foreach ($slugs as $slug) {
      $clean = sanitize_text_field((string) $slug);
      if (isset($allowed[$clean])) {
        $out[] = $clean;
      }
    }
    return $out;
  }

  /**
   * Filter an array of slugs to only those allowed for Activities.
   *
   * @param string[] $slugs
   * @return string[]
   */
  public static function filter_allowed_activities(array $slugs): array {
    $allowed = array_flip(self::ACTIVITIES_SLUGS);
    $out = [];
    foreach ($slugs as $slug) {
      $clean = sanitize_text_field((string) $slug);
      if (isset($allowed[$clean])) {
        $out[] = $clean;
      }
    }
    return $out;
  }
}
