<?php

declare(strict_types = 1);

namespace Mortezakarimi\CbdInformationAnalyzer\Includes;

use Mortezakarimi\CbdInformationAnalyzer\Admin\cbdInformationAnalyzerAdmin;
use Mortezakarimi\CbdInformationAnalyzer\Public\cbdInformationAnalyzerPublic;

/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @see       https://morteza-karimi.ir
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 *
 * @author     Morteza Karimi <me@morteza-karimi.ir>
 */
class cbdInformationAnalyzer
{
  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    1.0.0
   *
   * @var cbdInformationAnalyzerLoader maintains and registers all hooks for the plugin
   */
  protected cbdInformationAnalyzerLoader $loader;

  /**
   * The unique identifier of this plugin.
   *
   * @since    1.0.0
   *
   * @var string the string used to uniquely identify this plugin
   */
  protected string $plugin_name;

  /**
   * The current version of the plugin.
   *
   * @since    1.0.0
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
   * @since    1.0.0
   */
  public function __construct()
  {
    if ( \defined( 'CBD_INFORMATION_ANALYZER_VERSION' ) ) {
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
   * Run the loader to execute all of the hooks with WordPress.
   *
   * @since    1.0.0
   */
  public function run(): void
  {
    $this->loader->run();
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @return string the name of the plugin
   *
   * @since     1.0.0
   */
  public function get_plugin_name(): string
  {
    return $this->plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @return cbdInformationAnalyzerLoader orchestrates the hooks of the plugin
   *
   * @since     1.0.0
   */
  public function get_loader(): cbdInformationAnalyzerLoader
  {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @return string the version number of the plugin
   *
   * @since     1.0.0
   */
  public function get_version(): string
  {
    return $this->version;
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
   * @since    1.0.0
   */
  private function load_dependencies(): void
  {
    $this->loader = new cbdInformationAnalyzerLoader();
  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Cbd_Information_Analyzer_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   */
  private function set_locale(): void
  {
    $plugin_i18n = new cbdInformationAnalyzerI18N();

    $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
  }

  /**
   * Register all of the hooks related to the admin area functionality
   * of the plugin.
   *
   * @since    1.0.0
   */
  private function define_admin_hooks(): void
  {
    $plugin_admin = new cbdInformationAnalyzerAdmin( $this->get_plugin_name(), $this->get_version() );

    $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
    $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_action' );
  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   *
   * @since    1.0.0
   */
  private function define_public_hooks(): void
  {
    $plugin_public = new cbdInformationAnalyzerPublic( $this->get_plugin_name(), $this->get_version() );

    $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
    $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
  }
}
