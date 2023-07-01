<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://morteza-karimi.ir
 * @since             1.0.0
 * @package           Cbd_Information_Analyzer
 *
 * @wordpress-plugin
 * Plugin Name:       CBD Information Analyzer
 * Plugin URI:        https://morteza-karimi.ir
 * Description:       Load and process CBD information
 * Version:           1.0.0
 * Author:            Morteza Karimi
 * Author URI:        https://morteza-karimi.ir
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cbd-information-analyzer-textdomain
 * Domain Path:       /languages
 */

declare( strict_types=1 );

// If this file is called directly, abort.
use Cbd_Information_Analyzer\Admin\models\UserHistory;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzer;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerActivator;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDeactivator;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ContextProvider\CliContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\ServerDumper;
use Symfony\Component\VarDumper\VarDumper;

if ( ! defined( 'WPINC' ) ) {
	exit( 0 );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const CBD_INFORMATION_ANALYZER_VERSION = '1.0.0';
define( "ADMIN_VIEWS_BASE", plugin_dir_path( __FILE__ ) . 'src/Admin/partials' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cbd-information-analyzer-activator.php.
 */
function cbd_information_analyzer_activate(): void {
	QM::critical( "TEST SETS ETST" );
	CbdInformationAnalyzerActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cbd-information-analyzer-deactivator.php.
 */
function cbd_information_analyzer_deactivate(): void {
	CbdInformationAnalyzerDeactivator::deactivate();
}

$cloner         = new VarCloner();
$fallbackDumper = \in_array( \PHP_SAPI, [ 'cli', 'phpdbg' ] ) ? new CliDumper() : new HtmlDumper();
$dumper         = new ServerDumper( 'tcp://127.0.0.1:9912', $fallbackDumper, [
	'cli'    => new CliContextProvider(),
	'source' => new SourceContextProvider(),
] );

VarDumper::setHandler( function ( $var ) use ( $cloner, $dumper ) {
	$dumper->dump( $cloner->cloneVar( $var ) );
} );
register_activation_hook( __FILE__, 'cbd_information_analyzer_activate' );
register_deactivation_hook( __FILE__, 'cbd_information_analyzer_deactivate' );

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
function cbd_information_analyzer_run(): void {
	$plugin = new CbdInformationAnalyzer();
	$plugin->run();
}

cbd_information_analyzer_run();

