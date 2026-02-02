<?php
/**
 * Plugin Name: Parks GeoJSON Map
 * Description: Imports park GeoJSON polygons and renders a map via [parks_map].
 * Version: 0.1
 */

defined('ABSPATH') || exit;

// Load modules
require_once plugin_dir_path(__FILE__) . 'includes/taxonomies.php';
require_once plugin_dir_path(__FILE__) . 'includes/maps-render.php';

// Models + Repository
require_once plugin_dir_path(__FILE__) . 'includes/Models/Park.php';
require_once plugin_dir_path(__FILE__) . 'includes/Models/ParkInfo.php';
require_once plugin_dir_path(__FILE__) . 'includes/Models/ParkGeometry.php';

require_once plugin_dir_path(__FILE__) . 'includes/Repositories/ParkRepository.php';
require_once plugin_dir_path(__FILE__) . 'includes/ParkFactory.php';
