<?php
/**
 * CPT OC-Park and taxonomies (Amenities, Activities) are registered elsewhere.
 * This file provides plugin-level helpers and hooks related to those taxonomies.
 */

defined('ABSPATH') || exit;

/**
 * Render an unordered list of terms for a given taxonomy on the current post.
 *
 * @param string $taxonomy Taxonomy slug.
 * @return string HTML <ul>…</ul> or empty string when no terms/post.
 */
function parks_geojson_render_terms_list_for_current_post(string $taxonomy): string {
  $post_id = get_the_ID();
  if (!$post_id) {
    return '';
  }

  $terms = get_the_terms($post_id, $taxonomy);
  if (is_wp_error($terms) || empty($terms)) {
    return '';
  }

  $items = [];
  foreach ($terms as $term) {
    if (!isset($term->name)) {
      continue;
    }
    $items[] = sprintf(
      '<li>%s</li>',
      esc_html($term->name)
    );
  }

  if (empty($items)) {
    return '';
  }

  return sprintf(
    '<ul class="parks-geojson-%s-list">%s</ul>',
    esc_attr($taxonomy),
    implode('', $items)
  );
}

/**
 * Shortcode [park_amenities_list]: bulleted list of Amenities terms.
 */
add_shortcode('park_amenities_list', function (): string {
  if (!class_exists('TaxonomyConstants')) {
    return '';
  }
  return parks_geojson_render_terms_list_for_current_post(TaxonomyConstants::TAX_AMENITIES);
});

/**
 * Shortcode [park_activities_list]: bulleted list of Activities terms.
 */
add_shortcode('park_activities_list', function (): string {
  if (!class_exists('TaxonomyConstants')) {
    return '';
  }
  return parks_geojson_render_terms_list_for_current_post(TaxonomyConstants::TAX_ACTIVITIES);
});

