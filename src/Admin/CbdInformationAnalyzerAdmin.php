<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

namespace Cbd_Information_Analyzer\Admin;

use Cbd_Information_Analyzer\Admin\services\ProductService;
use Cbd_Information_Analyzer\Admin\services\UserHistoryService;
use Cbd_Information_Analyzer\Admin\services\UserTargetService;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerRoles;
use PhpOffice\PhpSpreadsheet\Exception;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzerAdmin {

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
	 * @param string $plugin_name the name of this plugin.
	 * @param string $version the version of this plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Show html page.
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function show_report(): void {
		include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-display.php';
	}

	/**
	 * SKU Import html page.
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function sku_import(): void {
		include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-sku-import.php';
	}

	public static function get_child_users( $user_id ) {
		$cache_key = 'child_users_for_user_' . $user_id;
		$children  = get_transient( $cache_key );

		if ( false === $children ) {
			$children = array();
			if ( user_can( $user_id, CbdInformationAnalyzerRoles::ROLE_GOD ) ) {
				$users = get_users();
			} else {
				/** @var \WP_User[] $users */
				$users = get_users( array(
					'fields'     => array( 'ID', 'parent' ),
					'meta_key'   => 'parent',
					'meta_value' => $user_id,
				) );
			}
			foreach ( $users as $user ) {
				$children[] = $user->ID;
				if ( ! user_can( $user_id, CbdInformationAnalyzerRoles::ROLE_GOD ) ) {
					$children += self::get_child_users( $user->ID );
				}
			}

			// Store the data in the cache for 1 hour
			set_transient( $cache_key, $children, HOUR_IN_SECONDS );
		}

		return $children;
	}

	/**
	 * Register the stylesheets for the admin area.
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
			plugin_dir_url( __FILE__ ) . 'css/cbd-information-analyzer-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the admin area.
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
		wp_enqueue_script( 'chart-js',
			'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.2.1/chart.umd.js',
			array( 'jquery' ),
			'4.2.1',
			true );
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/cbd-information-analyzer-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);
	}

	/**
	 * Add plugin admin menu items
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function add_menu_action(): void {
		add_menu_page(
			__( 'CBD Analyzer Report', 'cbd-information-analyzer-textdomain' ),
			__( 'CBD Analyzer', 'cbd-information-analyzer-textdomain' ),
			'read_reports',
			'cbd-analyzer',
			array( $this, 'show_report' ),
			'dashicons-analytics',
			100
		);
		add_submenu_page(
			'cbd-analyzer',
			__( 'Reports', 'cbd-information-analyzer-textdomain' ),
			__( 'Reports', 'cbd-information-analyzer-textdomain' ),
			'read_reports',
			'cbd-analyzer',
			array( $this, 'show_report' )
		);
		add_submenu_page(
			'cbd-analyzer',
			__( 'Import History Information', 'cbd-information-analyzer-textdomain' ),
			__( 'Import History', 'cbd-information-analyzer-textdomain' ),
			'add_actual',
			'cbd-analyzer-history-import',
			static function (): void {
				include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-month-history-import.php';
			},
		);
		add_submenu_page(
			'cbd-analyzer',
			__( 'Import Month Target Information', 'cbd-information-analyzer-textdomain' ),
			__( 'Import Month Target', 'cbd-information-analyzer-textdomain' ),
			'add_final_actual',
			'cbd-analyzer-month-target-import',
			static function (): void {
				include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-month-target-import.php';
			},
		);
		add_submenu_page(
			'cbd-analyzer',
			__( 'Import Products Information', 'cbd-information-analyzer-textdomain' ),
			__( 'Import Products', 'cbd-information-analyzer-textdomain' ),
			'manage_options',
			'cbd-analyzer-product-import',
			static function (): void {
				include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-product-import.php';
			},
		);
		add_submenu_page(
			'cbd-analyzer',
			__( 'Import User Roles Information', 'cbd-information-analyzer-textdomain' ),
			__( 'Import User Roles', 'cbd-information-analyzer-textdomain' ),
			'manage_options',
			'cbd-analyzer-user-relation-import',
			static function (): void {
				include ADMIN_VIEWS_BASE . '/cbd-information-analyzer-admin-user-relation-import.php';
			},
		);

//		add_action('admin_init',function (){
//			var_dump(wp_get_current_user()->allcaps);
//		});
	}

	/**
	 * @throws Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function download_products_example() {
		ProductService::handleGenerateExample();
	}

	/**
	 * @throws Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function download_monthly_target_example() {
		UserTargetService::handleGenerateExample();
	}

	/**
	 * @throws Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function download_history_example() {
		UserHistoryService::handleGenerateExample();
	}

	public function add_user_meta_column( $columns ) {
		$columns['position'] = __( 'Position', 'cbd-information-analyzer-textdomain' );
		$columns['area']     = __( 'Area', 'cbd-information-analyzer-textdomain' );
		$columns['region']   = __( 'Region', 'cbd-information-analyzer-textdomain' );

		return $columns;
	}

	public function populate_user_meta_column( $value, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'position':
			case 'area':
			case 'region':
				$meta_data = get_user_meta( $user_id,
					$column_name,
					true ); // Replace meta_key with the actual meta key you want to display
				if ( $meta_data ) {
					$value = esc_html( $meta_data );
				}
				break;
		}

		return $value;
	}
}
