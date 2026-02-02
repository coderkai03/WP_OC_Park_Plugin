<?php
/**
 * Persists Park to OC-Park CPT using ACF fields and Amenities/Activities taxonomies.
 * CPT and taxonomies are registered elsewhere.
 */

defined('ABSPATH') || exit;

class ParkRepository {

  private const POST_TYPE = 'OC-Park';

  /**
   * UPSERT park by global_id (creates or updates OC-Park post).
   * Only whitelisted taxonomy term slugs (TaxonomyConstants) are assigned.
   */
  public static function upsert(Park $park): int {

    $post_id = self::find_by_global_id($park->global_id);

    if (!$post_id) {
      $post_id = wp_insert_post([
        'post_type'   => self::POST_TYPE,
        'post_title'  => $park->info->name,
        'post_status' => 'publish',
      ]);
    }

    // ACF fields
    update_post_meta($post_id, 'park_name', $park->info->name);
    update_post_meta($post_id, 'park_address', $park->info->address);
    update_post_meta($post_id, 'park_type', $park->info->type);
    update_post_meta($post_id, 'park_size', $park->info->size);
    update_post_meta($post_id, 'park_url', $park->info->url);

    update_post_meta($post_id, 'park_global_id', $park->global_id);
    update_post_meta($post_id, 'park_geometry', json_encode($park->geometry));

    // Only assign terms that are in the allowed lists (and optionally exist in WP)
    $amenities = TaxonomyConstants::filter_allowed_amenities($park->amenities);
    $activities = TaxonomyConstants::filter_allowed_activities($park->activities);
    wp_set_object_terms($post_id, $amenities, TaxonomyConstants::TAX_AMENITIES);
    wp_set_object_terms($post_id, $activities, TaxonomyConstants::TAX_ACTIVITIES);

    return $post_id;
  }

  private static function find_by_global_id(string $global_id): ?int {

    $query = new WP_Query([
      'post_type'      => self::POST_TYPE,
      'meta_query'     => [[
        'key'   => 'park_global_id',
        'value' => $global_id,
      ]],
      'posts_per_page' => 1,
    ]);

    return $query->have_posts() ? (int) $query->posts[0]->ID : null;
  }
}
