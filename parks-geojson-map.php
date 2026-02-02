<?php
/**
 * Plugin Name: Parks GeoJSON Map
 * Description: Imports park GeoJSON polygons and renders a map via [parks_map].
 * Version: 0.6
 */

defined('ABSPATH') || exit;

// Load order: TaxonomyConstants before Repository/Factory/Mapper; admin only when is_admin
require_once plugin_dir_path(__FILE__) . 'includes/taxonomies.php';
require_once plugin_dir_path(__FILE__) . 'includes/TaxonomyConstants.php';

require_once plugin_dir_path(__FILE__) . 'includes/Models/Park.php';
require_once plugin_dir_path(__FILE__) . 'includes/Models/ParkInfo.php';
require_once plugin_dir_path(__FILE__) . 'includes/Models/ParkGeometry.php';

require_once plugin_dir_path(__FILE__) . 'includes/Repositories/ParkRepository.php';
require_once plugin_dir_path(__FILE__) . 'includes/ParkFactory.php';
require_once plugin_dir_path(__FILE__) . 'includes/GeoJsonToParkMapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/GeoJsonInputValidator.php';

require_once plugin_dir_path(__FILE__) . 'includes/maps-render.php';

if (is_admin()) {
  require_once plugin_dir_path(__FILE__) . 'includes/admin-import.php';
}
