<?php
// Shortcode: renders map for the current park only
add_shortcode('parks_map', function ($atts) {

  $post_id = get_the_ID();

  if (!$post_id) {
    return '<p>No park found.</p>';
  }

  $park = ParkFactory::from_post($post_id);

  ob_start();
  ?>
    <div id="parks-map" style="width:100%;height:500px;"></div>

    <script>
      window.PARK_DATA = <?= json_encode($park); ?>;
      console.log("Loaded park:", window.PARK_DATA);
    </script>
  <?php
  return ob_get_clean();
});
