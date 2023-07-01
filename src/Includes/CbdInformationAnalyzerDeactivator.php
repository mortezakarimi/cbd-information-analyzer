<?php
/**
 * Fired during plugin deactivation.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare(strict_types = 1);

namespace Cbd_Information_Analyzer\Includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 *
 * @author Morteza Karimi <me@morteza-karimi.ir>
 */
class CbdInformationAnalyzerDeactivator {

	/**
	 * Short Description. (use period).
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate(): void {
		CbdInformationAnalyzerRoles::remove();
	}
}
