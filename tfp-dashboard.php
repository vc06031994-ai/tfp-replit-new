<?php
/**
 * Plugin Name: TFP Dashboard
 * Description: Custom-coded student dashboard (Home, Grades, Communication, Documents, Calendar, Profile) for The Follow Project. Reuses helper functions from the TFP Authentication plugin. Not built with Elementor — fully custom templates for app-like behaviour and pixel-perfect design control.
 * Version: 1.2.2
 * Author: The Follow Project
 * Text Domain: tfp-dashboard
 */

if (!defined('ABSPATH')) exit;

define('TFP_DASH_VERSION', '1.2.4');
define('TFP_DASH_PATH', plugin_dir_path(__FILE__));
define('TFP_DASH_URL', plugin_dir_url(__FILE__));

/**
 * Activation: create the chat messages table.
 */
register_activation_hook(__FILE__, function () {
    global $wpdb;