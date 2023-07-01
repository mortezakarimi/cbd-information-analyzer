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
class CbdInformationAnalyzerDatabase {
	public const PRODUCT_TABLE = 'cbd_information_analyzer_product';
	public const SKU_USER_TARGET = 'cbd_information_analyzer_sku_user_target';
	public const SKU_USER_HISTORY = 'cbd_information_analyzer_sku_user_history';

	/**
	 * init database structure
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function init_database_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		self::init_sku_table( $wpdb, $charset );
		self::init_sku_user_history_table( $wpdb, $charset );
		self::init_sku_user_target_table( $wpdb, $charset );
	}

	/**
	 * @param \wpdb $wpdb
	 * @param string $charset
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function init_sku_table( \wpdb $wpdb, $charset ): void {
		$tablename = $wpdb->prefix . self::PRODUCT_TABLE;
		$sql       = "CREATE TABLE $tablename (
      ID bigint(20) unsigned NOT NULL PRIMARY KEY,
      sku_name varchar(255) NOT NULL DEFAULT '',
      name varchar(255) NOT NULL DEFAULT '',
      group_name varchar(255) NOT NULL DEFAULT '',
      createdAt datetime NOT NULL  DEFAULT '1000-01-01 00:00:00',
      updatedAt datetime NOT NULL  DEFAULT '1000-01-01 00:00:00'
    ) $charset; ENGINE=InnoDB";
		dbDelta( $sql );
	}

	/**
	 * @param \wpdb $wpdb
	 * @param string $charset
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function init_sku_user_history_table( \wpdb $wpdb, string $charset ): void {
		$tablename     = $wpdb->prefix . self::SKU_USER_HISTORY;
		$product_table = $wpdb->prefix . self::PRODUCT_TABLE;
		$sql           = "CREATE TABLE $tablename (
      SKU_ID bigint(20) unsigned NOT NULL,
      USER_ID bigint(20) unsigned NOT NULL,
      changeAt date NOT NULL DEFAULT '1000-01-01',
      createdAt datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
      updatedAt datetime NOT NULL  DEFAULT '1000-01-01 00:00:00',
      amount bigint(20) NOT NULL DEFAULT 0,
      PRIMARY KEY  (SKU_ID, USER_ID,changeAt),
      CONSTRAINT history_sku_fk FOREIGN KEY (SKU_ID) REFERENCES $product_table(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
      CONSTRAINT history_user_fk FOREIGN KEY (USER_ID) REFERENCES $wpdb->users(ID) ON DELETE CASCADE ON UPDATE NO ACTION
    ) $charset; ENGINE=InnoDB";
		dbDelta( $sql );
	}

	/**
	 * @param \wpdb $wpdb
	 * @param string $charset
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function init_sku_user_target_table( \wpdb $wpdb, string $charset ): void {
		$tablename     = $wpdb->prefix . self::SKU_USER_TARGET;
		$product_table = $wpdb->prefix . self::PRODUCT_TABLE;
		$sql           = "CREATE TABLE $tablename (
      SKU_ID bigint(20) unsigned NOT NULL,
      USER_ID bigint(20) unsigned NOT NULL,
      target_year YEAR(4) NOT NULL DEFAULT '0000',
      target_month TINYINT(2) NOT NULL  DEFAULT 1,
      total_working_days TINYINT(2) NOT NULL DEFAULT 30,
      createdAt datetime NOT NULL  DEFAULT '1000-01-01 00:00:00',
      updatedAt datetime NOT NULL  DEFAULT '1000-01-01 00:00:00',
      amount bigint(20) NOT NULL DEFAULT 0,
      actual bigint(20) DEFAULT NULL,
      PRIMARY KEY  (SKU_ID,USER_ID,target_year,target_month),
      CONSTRAINT target_sku_fk FOREIGN KEY (SKU_ID) REFERENCES $product_table(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
      CONSTRAINT target_user_fk FOREIGN KEY (USER_ID) REFERENCES $wpdb->users(ID) ON DELETE CASCADE ON UPDATE NO ACTION
    ) $charset; ENGINE=InnoDB";
		dbDelta( $sql );
	}
}
