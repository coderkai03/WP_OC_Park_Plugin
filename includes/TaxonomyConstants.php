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
  public const TAX_AMENITIES = 'amenities';

  /** Taxonomy slug for Activities (e.g. soccer, playground). */
  public const TAX_ACTIVITIES = 'activities';

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
}
