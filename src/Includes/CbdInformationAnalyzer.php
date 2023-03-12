<?php
/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

namespace Cbd_Information_Analyzer\Includes;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\AppPublic\CbdInformationAnalyzerPublic;

use function defined;


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since 1.0.0
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzer {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var CbdInformationAnalyzerLoader maintains and registers all hooks for the plugin
	 */
	protected CbdInformationAnalyzerLoader $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string the string used to uniquely identify this plugin
	 */
	protected string $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string the current version of the plugin
	 */
	protected string $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'CBD_INFORMATION_ANALYZER_VERSION' ) ) {
			$this->version = CBD_INFORMATION_ANALYZER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'cbd-information-analyzer';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Cbd_Information_Analyzer_Loader. Orchestrates the hooks of the plugin.
	 * - Cbd_Information_Analyzer_i18n. Defines internationalization functionality.
	 * - Cbd_Information_Analyzer_Admin. Defines all hooks for the admin area.
	 * - Cbd_Information_Analyzer_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies(): void {
		$this->loader = new CbdInformationAnalyzerLoader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Cbd_Information_Analyzer_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0.0
	 */
	private function set_locale(): void {
		$plugin_i18n = new CbdInformationAnalyzerI18N();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new CbdInformationAnalyzerAdmin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_action' );

		$this->loader->add_action( 'admin_post_download_products_example', $plugin_admin, 'download_products_example' );
		$this->loader->add_action( 'admin_post_download_monthly_target_example',
			$plugin_admin,
			'download_monthly_target_example' );
		$this->loader->add_action( 'admin_post_download_history_example',
			$plugin_admin,
			'download_history_example' );
		$this->loader->add_filter( 'manage_users_columns', $plugin_admin, 'add_user_meta_column' );
		$this->loader->add_filter( 'manage_users_custom_column', $plugin_admin, 'populate_user_meta_column', 10, 3 );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string the name of the plugin
	 *
	 * @since 1.0.0
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string the version number of the plugin
	 *
	 * @since 1.0.0
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function define_public_hooks(): void {
		$plugin_public = new CbdInformationAnalyzerPublic( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return CbdInformationAnalyzerLoader orchestrates the hooks of the plugin
	 *
	 * @since 1.0.0
	 */
	public function get_loader(): CbdInformationAnalyzerLoader {
		return $this->loader;
	}

}
