<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

namespace Cbd_Information_Analyzer\User;

use Cbd_Information_Analyzer\Admin\services\UserProfileService;
use DateTime;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzerUser {

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

	function generate_file_init() {
		if ( ! empty( $_POST['generate_file'] ) && isset( $_POST['form_nonce'] ) && wp_verify_nonce( $_POST['form_nonce'], 'report-user-nonce' ) ) {
			$user = wp_get_current_user();
			if ( null === $user ) {
				return '';
			}
			$attr['low-style']  = sanitize_text_field( $_POST['low-style'] );
			$attr['mid-style']  = sanitize_text_field( $_POST['mid-style'] );
			$attr['high-style'] = sanitize_text_field( $_POST['high-style'] );
			$attr['date']       = sanitize_text_field( $_POST['date'] );
			$selected_export    = sanitize_text_field( $_POST['generate_file'] );

			$month    = ( new DateTime( $attr['date'] ?? 'now' ) )->format( 'n' );
			$year     = ( new DateTime( $attr['date'] ?? 'now' ) )->format( 'Y' );
			$products = UserProfileService::calculatePageInformation( $attr, $user );
			// Get selected option value
			if ( UserProfileService::DOWNLOAD_XLSX === $selected_export ) {
				UserProfileService::generateXlsx( $user, $month, $year, $products );
			}
			if ( UserProfileService::DOWNLOAD_PDF === $selected_export ) {
				UserProfileService::generatePDF( $user, $month, $year, $products );
				exit( 0 );
			}
		}
	}
}
