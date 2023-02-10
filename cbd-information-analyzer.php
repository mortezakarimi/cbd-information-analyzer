<?php

declare(strict_types = 1);
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
  exit( 0 );
}

use Mortezakarimi\CbdInformationAnalyzer\Includes\cbdInformationAnalyzer;
use Mortezakarimi\CbdInformationAnalyzer\Includes\cbdInformationAnalyzerActivator;
use Mortezakarimi\CbdInformationAnalyzer\Includes\cbdInformationAnalyzerDeactivator;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
/**
 * The plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @see              https://morteza-karimi.ir
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       CBD Information Analyzer
 * Description:       Load and process CBD information
 * Version:           1.0.0
 * Author:            Morteza Karimi
 * Author URI:        https://morteza-karimi.ir
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cbd-information-analyzer
 * Domain Path:       /languages
 */

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const CBD_INFORMATION_ANALYZER_VERSION = '1.0.0';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cbd-information-analyzer-activator.php.
 */
function activate_cbd_information_analyzer(): void
{
  cbdInformationAnalyzerActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cbd-information-analyzer-deactivator.php.
 */
function deactivate_cbd_information_analyzer(): void
{
  cbdInformationAnalyzerDeactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cbd_information_analyzer' );
register_deactivation_hook( __FILE__, 'deactivate_cbd_information_analyzer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cbd_information_analyzer(): void
{
  $plugin = new cbdInformationAnalyzer();
  $plugin->run();
}

run_cbd_information_analyzer();
