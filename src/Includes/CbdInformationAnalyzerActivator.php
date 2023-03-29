<?php
/**
 * Fired during plugin activation.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

namespace Cbd_Information_Analyzer\Includes;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzerActivator {

	/**
	 * Short Description. (use period).
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		try {
			CbdInformationAnalyzerDatabase::init_database_tables();
			CbdInformationAnalyzerRoles::add();
			wp_cache_flush();
		} catch ( \Exception $exception ) {
			die( $exception->getMessage() );
		}
	}
}
