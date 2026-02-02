<?php
/**
 * Shortcode [parks_map]: renders map container, outputs window.PARK_DATA,
 * and enqueues Google Maps API + plugin script when an API key is set.
 * Tooltip content comes from PARK_DATA.info (name, address, etc.).
 */

defined('ABSPATH') || exit;

add_shortcode('parks_map', function ($atts) {

  $post_id = get_the_ID();

  if (!$post_id) {
    return '<p>No park found.</p>';
  }

  $park = ParkFactory::from_post($post_id);

  // API key from options (set on Settings > Parks GeoJSON Import)
  $api_key = get_option('parks_google_maps_api_key', '');
  if ($api_key === '') {
    return '<p>Parks map is not configured. Add a Google Maps API key under Settings &rarr; Parks GeoJSON Import.</p>';
  }

  // Enqueue Google Maps API then our script; both run after shortcode output so PARK_DATA is available
  wp_enqueue_script(
    'google-maps-api',
    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key),
    [],
    null,
    true
  );
  wp_enqueue_script(
    'parks-map',
    plugin_dir_url(dirname(__FILE__)) . 'assets/js/parks-map.js',
    ['google-maps-api'],
    '0.1',
    true
  );

  ob_start();
  ?>
  <div id="parks-map" style="width:100%;height:500px;"></div>
  <script>
    window.PARK_DATA = <?php echo wp_json_encode($park); ?>;
  </script>
  <?php
  return ob_get_clean();
});
