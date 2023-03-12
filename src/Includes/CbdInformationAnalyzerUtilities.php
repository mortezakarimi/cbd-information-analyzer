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
class CbdInformationAnalyzerUtilities {
	/**
	 * @param $setting
	 * @param $messages
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function setErrors( $setting, $messages ): void {
		if ( ! \is_array( $messages ) ) {
			$messages = [ $messages ];
		}
		foreach ( $messages as $message ) {
			$error_message = __( $message, 'cbd-information-analyzer-textdomain' );
			add_settings_error( 'cbd-information-analyzer-' . sanitize_title( $setting ),
				sanitize_title( $error_message ),
				$error_message,
				'error' );
		}
	}

	public static function showErrors($setting): void {
		settings_errors( 'cbd-information-analyzer-' . sanitize_title( $setting ) );
	}
}
