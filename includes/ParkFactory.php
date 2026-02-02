<?php
/**
 * Builds Park from OC-Park post using ACF fields and Amenities/Activities taxonomies.
 */

defined('ABSPATH') || exit;

class ParkFactory {

  /** Build Park from OC-Park post ID. */
  public static function from_post(int $post_id): Park {

    $park = new Park();
    $park->global_id = get_post_meta($post_id, 'park_global_id', true) ?: '';

    $park->info = new ParkInfo(
      get_post_meta($post_id, 'park_name', true) ?: get_the_title($post_id),
      get_post_meta($post_id, 'park_address', true) ?: '',
      get_post_meta($post_id, 'park_type', true) ?: '',
      get_post_meta($post_id, 'park_size', true) ?: '',
      get_post_meta($post_id, 'park_url', true) ?: '',
    );

    $geometry_json = get_post_meta($post_id, 'park_geometry', true);
    $geometry = is_string($geometry_json) ? json_decode($geometry_json, true) : [];
    $park->geometry = new ParkGeometry(
      $geometry['type'] ?? 'Polygon',
      $geometry['coordinates'] ?? []
    );

    $amenities = get_the_terms($post_id, TaxonomyConstants::TAX_AMENITIES);
    $park->amenities = $amenities && ! is_wp_error($amenities) ? wp_list_pluck($amenities, 'slug') : [];

    $activities = get_the_terms($post_id, TaxonomyConstants::TAX_ACTIVITIES);
    $park->activities = $activities && ! is_wp_error($activities) ? wp_list_pluck($activities, 'slug') : [];

    return $park;
  }
}
