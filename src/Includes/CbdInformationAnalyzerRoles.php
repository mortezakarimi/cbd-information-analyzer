<?php
/**
 * Fired during plugin deactivation.
 *
 * @see   https://morteza-karimi.ir
 * @since 1.0.0
 * @package           Cbd_Information_Analyzer
 */

declare( strict_types=1 );

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
class CbdInformationAnalyzerRoles {
	public const ROLE_CBD = 'cbd';
	public const ROLE_PR = 'pr';
	public const ROLE_FSV = 'fsv';
	public const ROLE_ASM = 'asm';
	public const ROLE_RSM = 'rsm';
	public const ROLE_HTT = 'htt';
	public const ROLE_GM = 'gm';
	public const ROLE_SO = 'so';
	public const ROLE_GOD = 'god';

	/**
	 * Short Description. (use period).
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function add(): void {
		add_role(
			self::ROLE_CBD,
			'CBD',
			[
				'read_reports' => true
			]
		);
		add_role(
			self::ROLE_PR,
			'PR',
			[
				'read_reports' => true
			]
		);

		add_role(
			self::ROLE_FSV,
			'FSV',
			array_merge(
				get_role( self::ROLE_CBD )->capabilities,
				get_role( self::ROLE_PR )->capabilities,
				[
					'add_actual' => true,
				]
			)
		);

		add_role(
			self::ROLE_ASM,
			'ASM',
			array_merge(
				get_role( self::ROLE_FSV )->capabilities,
				[
					'add_target'       => true,
					'add_final_actual' => true
				]
			)
		);

		add_role(
			self::ROLE_RSM,
			'RSM',
			get_role( self::ROLE_ASM )->capabilities,
		);
		add_role(
			self::ROLE_HTT,
			'HTT',
			get_role( self::ROLE_RSM )->capabilities
		);
		add_role(
			self::ROLE_GM,
			'GM',
			get_role( self::ROLE_RSM )->capabilities
		);
		add_role(
			self::ROLE_SO,
			'SO',
			get_role( self::ROLE_RSM )->capabilities
		);
		add_role(
			self::ROLE_GOD,
			'God',
			array_merge(
				get_role( self::ROLE_HTT )->capabilities,
				get_role( self::ROLE_GM )->capabilities,
				get_role( self::ROLE_SO )->capabilities,
				[
					'manage_target'         => true,
					'read_product'          => true,
					'manage_product'        => true,
					'manage_actual'         => true,
					'manage_final_actual'   => true,
					'manage_user_relations' => true,
				]
			)
		);

		$administrator_role = get_role( 'administrator' );
		if ( $administrator_role instanceof \WP_Role ) {
			foreach ( get_role( self::ROLE_GOD )->capabilities as $capability => $isGrant ) {
				$administrator_role->add_cap( $capability, $isGrant );
			}
		}
	}

	/**
	 * Short Description. (use period).
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function remove(): void {
		remove_role( self::ROLE_CBD );
		remove_role( self::ROLE_PR );
		remove_role( self::ROLE_FSV );
		remove_role( self::ROLE_ASM );
		remove_role( self::ROLE_RSM );
		remove_role( self::ROLE_HTT );
		remove_role( self::ROLE_GM );
		remove_role( self::ROLE_SO );
		remove_role( self::ROLE_GOD );
	}

	public static function getAvailableRoles() {
		return [
			self::ROLE_CBD => strtoupper( self::ROLE_CBD ),
			self::ROLE_PR  => strtoupper( self::ROLE_PR ),
			self::ROLE_FSV => strtoupper( self::ROLE_FSV ),
			self::ROLE_ASM => strtoupper( self::ROLE_ASM ),
			self::ROLE_RSM => strtoupper( self::ROLE_RSM ),
			self::ROLE_HTT => strtoupper( self::ROLE_HTT ),
			self::ROLE_GM  => strtoupper( self::ROLE_GM ),
			self::ROLE_SO  => strtoupper( self::ROLE_SO ),
			self::ROLE_GOD => strtoupper( self::ROLE_GOD ),
		];
	}
}
