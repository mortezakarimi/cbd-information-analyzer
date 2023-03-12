<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

namespace Cbd_Information_Analyzer\AppPublic;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzerPublic {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string the ID of this plugin
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string the current version of this plugin
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name the name of the plugin.
	 * @param string $version the version of this plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles(): void {
		/*
		* This function is provided for demonstration purposes only.
		*
		* An instance of this class should be passed to the run() function
		* defined in Cbd_Information_Analyzer_Loader as all of the hooks are defined
		* in that particular class.
		*
		* The Cbd_Information_Analyzer_Loader will then create the relationship
		* between the defined hooks and the functions defined in this
		* class.
		*/

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/cbd-information-analyzer-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts(): void {
		/*
		* This function is provided for demonstration purposes only.
		*
		* An instance of this class should be passed to the run() function
		* defined in Cbd_Information_Analyzer_Loader as all of the hooks are defined
		* in that particular class.
		*
		* The Cbd_Information_Analyzer_Loader will then create the relationship
		* between the defined hooks and the functions defined in this
		* class.
		*/

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/cbd-information-analyzer-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);
	}
}
