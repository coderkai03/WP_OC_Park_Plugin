<?php
/**
 * Admin screen: Import Parks GeoJSON and Parks Map settings (Google Maps API key).
 * Registers menu, form, nonce/capability checks, file validation, and delegates mapping to GeoJsonToParkMapper.
 */

defined('ABSPATH') || exit;

// Nonce actions and option key for forms and storage
const PARKS_IMPORT_NONCE_ACTION = 'parks_geojson_import';
const PARKS_MAPS_SETTINGS_NONCE_ACTION = 'parks_maps_settings';
const PARKS_GOOGLE_MAPS_API_KEY_OPTION = 'parks_google_maps_api_key';

/**
 * Lightweight debug logger for import troubleshooting.
 * Enable by defining PARKS_GEOJSON_MAP_DEBUG to true in wp-config.php.
 */
function parks_geojson_debug_enabled(): bool {
  return defined('PARKS_GEOJSON_MAP_DEBUG') && PARKS_GEOJSON_MAP_DEBUG;
}

function parks_geojson_log(string $message, array $context = []): void {
  if (! parks_geojson_debug_enabled()) {
    return;
  }
  $prefix = '[parks-geojson-map] ';
  $ctx = $context ? ' ' . wp_json_encode($context) : '';
  error_log($prefix . $message . $ctx);
}

/**
 * Register admin menu: add "Parks GeoJSON Import" under Settings.
 */
add_action('admin_menu', function () {
  add_options_page(
    'Import Parks GeoJSON',
    'Parks GeoJSON Import',
    'manage_options',
    'parks-geojson-import',
    'parks_render_import_page'
  );
});

/**
 * Handle POST: save Google Maps API key from "Map settings" form.
 */
add_action('admin_init', function () {
  // Only run when the map settings form was submitted
  if (! isset($_POST['parks_save_maps_settings'], $_POST['_wpnonce']) ||
      ! current_user_can('manage_options')) {
    return;
  }
  if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), PARKS_MAPS_SETTINGS_NONCE_ACTION)) {
    return;
  }
  // Sanitize and save the API key to options; redirect back with success query arg
  $key = isset($_POST['parks_google_maps_api_key']) ? sanitize_text_field(wp_unslash($_POST['parks_google_maps_api_key'])) : '';
  update_option(PARKS_GOOGLE_MAPS_API_KEY_OPTION, $key);
  wp_safe_redirect(add_query_arg('maps_updated', '1', wp_get_referer() ?: admin_url('options-general.php?page=parks-geojson-import')));
  exit;
});

/**
 * Handle POST: import GeoJSON file from "Import GeoJSON" form.
 */
add_action('admin_init', function () {
  // Bail unless the import form was submitted and user can manage options
  if (! isset($_POST['parks_import_geojson'], $_POST['_wpnonce']) || ! current_user_can('manage_options')) {
    return;
  }

  parks_geojson_log('Import requested', [
    'user_id' => get_current_user_id(),
    'file_name' => isset($_FILES['parks_geojson_file']['name']) ? (string) $_FILES['parks_geojson_file']['name'] : null,
    'file_size' => isset($_FILES['parks_geojson_file']['size']) ? (int) $_FILES['parks_geojson_file']['size'] : null,
    'file_type' => isset($_FILES['parks_geojson_file']['type']) ? (string) $_FILES['parks_geojson_file']['type'] : null,
  ]);

  try {
    // Verify nonce
    if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), PARKS_IMPORT_NONCE_ACTION)) {
      parks_geojson_log('Nonce verification failed');
      set_transient('parks_import_message', ['error', 'Security check failed.'], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }

    // Ensure a file was uploaded and is valid
    if (empty($_FILES['parks_geojson_file']['tmp_name']) || ! is_uploaded_file($_FILES['parks_geojson_file']['tmp_name'])) {
      parks_geojson_log('No upload / invalid temp file');
      set_transient('parks_import_message', ['error', 'Please choose a GeoJSON file to upload.'], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }

    // Read file contents from temp path
    $tmp_path = sanitize_text_field(wp_unslash($_FILES['parks_geojson_file']['tmp_name']));
    parks_geojson_log('Reading uploaded file', ['tmp_path' => $tmp_path]);

    $content = @file_get_contents($tmp_path);
    if ($content === false) {
      parks_geojson_log('file_get_contents failed', ['tmp_path' => $tmp_path]);
      set_transient('parks_import_message', ['error', 'Could not read the uploaded file.'], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }
    parks_geojson_log('File read ok', ['bytes' => strlen($content)]);

    // Decode JSON; require valid object
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
      parks_geojson_log('JSON decode failed', [
        'json_error' => json_last_error(),
        'json_error_msg' => function_exists('json_last_error_msg') ? json_last_error_msg() : null,
      ]);
      set_transient('parks_import_message', ['error', 'Invalid JSON in the uploaded file.'], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }
    parks_geojson_log('JSON decoded', ['root_type' => $data['type'] ?? null]);

    // Validate against input schema (type safety)
    parks_geojson_log('Validating against schema');
    $schema_errors = GeoJsonInputValidator::validate($data);
    if (! empty($schema_errors)) {
      parks_geojson_log('Schema validation failed', [
        'error_count' => count($schema_errors),
        'errors_head' => array_slice($schema_errors, 0, 10),
      ]);
      $message = implode(' ', array_slice($schema_errors, 0, 3));
      if (count($schema_errors) > 3) {
        $message .= ' …';
      }
      set_transient('parks_import_message', ['error', 'GeoJSON validation failed: ' . $message], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }

    // Require FeatureCollection with features array
    $features = $data['features'] ?? null;
    if (! is_array($features)) {
      parks_geojson_log('Missing/invalid features array');
      set_transient('parks_import_message', ['error', 'GeoJSON must be a FeatureCollection with a "features" array.'], 30);
      wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
      exit;
    }
    parks_geojson_log('Features loaded', ['count' => count($features)]);

    // Map each feature to Park and upsert; count imported vs skipped
    $imported = 0;
    $skipped = 0;
    foreach ($features as $i => $feature) {
      if (! is_array($feature)) {
        $skipped++;
        parks_geojson_log('Skipping non-object feature', ['index' => $i]);
        continue;
      }

      try {
        $park = GeoJsonToParkMapper::from_feature($feature);
      } catch (\Throwable $t) {
        $skipped++;
        parks_geojson_log('Mapper threw', [
          'index' => $i,
          'message' => $t->getMessage(),
          'file' => $t->getFile(),
          'line' => $t->getLine(),
        ]);
        continue;
      }

      if ($park === null) {
        $skipped++;
        parks_geojson_log('Skipping invalid feature (mapper returned null)', [
          'index' => $i,
          'feature_id' => $feature['id'] ?? null,
          'global_id' => $feature['properties']['GlobalID'] ?? null,
        ]);
        continue;
      }

      try {
        ParkRepository::upsert($park);
      } catch (\Throwable $t) {
        $skipped++;
        parks_geojson_log('Upsert threw', [
          'index' => $i,
          'global_id' => $park->global_id ?? null,
          'message' => $t->getMessage(),
          'file' => $t->getFile(),
          'line' => $t->getLine(),
        ]);
        continue;
      }

      $imported++;
    }
    parks_geojson_log('Import finished', ['imported' => $imported, 'skipped' => $skipped]);

    // Store success message in transient and redirect so it shows on next page load
    set_transient('parks_import_message', ['success', sprintf('%d park(s) imported.', $imported) . ($skipped ? " {$skipped} feature(s) skipped (invalid or missing id/geometry)." : '')], 30);
    wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
    exit;
  } catch (\Throwable $t) {
    // Catch-all so we never white-screen the admin during import
    parks_geojson_log('Import crashed (catch-all)', [
      'message' => $t->getMessage(),
      'file' => $t->getFile(),
      'line' => $t->getLine(),
    ]);
    set_transient('parks_import_message', ['error', 'Import failed due to a server error. Enable PARKS_GEOJSON_MAP_DEBUG for details.'], 30);
    wp_safe_redirect(admin_url('options-general.php?page=parks-geojson-import'));
    exit;
  }
});

/**
 * Render the Import + Settings page (Settings → Parks GeoJSON Import).
 */
function parks_render_import_page(): void {
  if (! current_user_can('manage_options')) {
    return;
  }

  // Show import result message from transient (set after redirect)
  $message = get_transient('parks_import_message');
  if ($message && is_array($message) && count($message) >= 2) {
    delete_transient('parks_import_message');
    $type = $message[0];
    $text = $message[1];
    echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
  }

  // Show "Map settings saved" when redirected with maps_updated=1
  if (isset($_GET['maps_updated'])) {
    echo '<div class="notice notice-success is-dismissible"><p>Map settings saved.</p></div>';
  }

  $api_key = get_option(PARKS_GOOGLE_MAPS_API_KEY_OPTION, '');
  ?>
  <div class="wrap">
    <h1>Parks GeoJSON Import</h1>

    <!-- Map settings: Google Maps API key (used by [parks_map] shortcode) -->
    <h2>Map settings</h2>
    <form method="post" action="">
      <?php wp_nonce_field(PARKS_MAPS_SETTINGS_NONCE_ACTION, '_wpnonce'); ?>
      <table class="form-table">
        <tr>
          <th scope="row"><label for="parks_google_maps_api_key">Google Maps API key</label></th>
          <td>
            <input type="text" id="parks_google_maps_api_key" name="parks_google_maps_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
            <p class="description">Required for the [parks_map] shortcode. Leave empty to disable map script.</p>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type="submit" name="parks_save_maps_settings" class="button button-primary" value="Save map settings" />
      </p>
    </form>

    <!-- Import form: upload GeoJSON FeatureCollection; each feature becomes/updates an OC-Park post -->
    <h2>Import GeoJSON</h2>
    <p>Upload a GeoJSON FeatureCollection file. Each feature must have <code>geometry</code> and a unique <code>id</code> or <code>properties.GlobalID</code>. See <code>docs/IMPORT.md</code> for expected property names.</p>
    <form method="post" action="" enctype="multipart/form-data">
      <?php wp_nonce_field(PARKS_IMPORT_NONCE_ACTION, '_wpnonce'); ?>
      <p>
        <input type="file" name="parks_geojson_file" accept=".geojson,.json,application/geo+json,application/json" required />
      </p>
      <p>
        <input type="submit" name="parks_import_geojson" class="button button-primary" value="Import GeoJSON" />
      </p>
    </form>
  </div>
  <?php
}
