<?php

namespace Cbd_Information_Analyzer\Admin\partials;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDatabase;

class User_History_List_Table extends \WP_List_Table {
	private $table_data;

	public function __construct() {
		parent::__construct( array(
				'singular' => 'month-history',
				'plural'   => 'month-histories',
				'ajax'     => false
		) );
	}

	public function prepare_items() {
		global $wpdb;
		$children = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$primary  = 'USER_ID';

		$data          = array();
		$table_name    = $wpdb->prefix . CbdInformationAnalyzerDatabase::SKU_USER_TARGET; // Replace "my_plugin_posts" with your custom table name
		$history_table = $wpdb->prefix . CbdInformationAnalyzerDatabase::SKU_USER_HISTORY; // Replace "my_plugin_posts" with your custom table name
		$product_table = $wpdb->prefix . CbdInformationAnalyzerDatabase::PRODUCT_TABLE; // Replace "my_plugin_posts" with your custom table name


		$paged  = isset( $_REQUEST['paged'] ) ? max( 0, (int) $_REQUEST['paged'] - 1 ) : 0;
		$offset = $paged * $per_page;

		$orderby = $_REQUEST['orderby'] ?? 'user_nicename';
		$order   = $_REQUEST['order'] ?? 'asc';

		$search = $_POST['s'] ?? null;

		$limit_date_range = $_REQUEST['filter-date-range'] ? explode( '-', $_REQUEST['filter-date-range'] ) : null;

		$tableWithJoins = "FROM $table_name `sut` LEFT JOIN $history_table wciasuh on sut.SKU_ID = wciasuh.SKU_ID AND sut.USER_ID = wciasuh.USER_ID AND sut.target_year = YEAR(wciasuh.changeAt) AND sut.target_month = MONTH(wciasuh.changeAt)  LEFT JOIN $wpdb->users `u` ON `sut`.USER_ID = `u`.ID LEFT JOIN $product_table `p` ON sut.SKU_ID = `p`.ID";
		$baseQuery      = "SELECT `p`.name as product_name, `sut`.USER_ID, sum(IF(wciasuh.amount, wciasuh.amount, 0)) as total, `sut`.amount as target,`sut`.actual as actual,`sut`.target_year as year,`sut`.target_month as month,max(`wciasuh`.updatedAt) updatedAt  $tableWithJoins";


		$limitRangeQuery = null;
		if ( \is_array( $limit_date_range ) && isset( $limit_date_range[0], $limit_date_range[1] ) ) {
			$limitRangeQuery = sprintf( "`sut`.target_year = '%s' AND `sut`.target_month = '%s'",
					$limit_date_range[0],
					$limit_date_range[1] );
		}
		if ( ! empty( $search ) ) {
			$countQuery = "SELECT COUNT(*) $tableWithJoins WHERE `sut`.USER_ID IN (" . implode( ',',
							$children ) . ") AND (`p`.name LIKE '%$search%' OR `u`.user_login LIKE '%$search%')" . ( $limitRangeQuery ? ' AND ' . $limitRangeQuery : '' );
			$dataQuery  = "$baseQuery WHERE `sut`.USER_ID IN (" . implode( ',',
							$children ) . ") AND (`p`.name LIKE '%$search%' OR `u`.user_login LIKE '%$search%') " . ( $limitRangeQuery ? ' AND ' . $limitRangeQuery : '' ) . ' GROUP BY sut.USER_ID, sut.SKU_ID, target_year, target_month' . " ORDER BY $orderby $order LIMIT $offset, $per_page";
		} else {
			$countQuery = "SELECT COUNT(sut.USER_ID) $tableWithJoins WHERE `sut`.USER_ID IN (" . implode( ',',
							$children ) . ')' . ( $limitRangeQuery ? ' AND ' . $limitRangeQuery : '' );
			$dataQuery  = "$baseQuery WHERE `sut`.USER_ID IN (" . implode( ',',
							$children ) . ')' . ( $limitRangeQuery ? ' AND ' . $limitRangeQuery : '' )
						  . ' GROUP BY sut.USER_ID, sut.SKU_ID, target_year, target_month' . " ORDER BY $orderby $order LIMIT $offset, $per_page";
		}

		$total_items = $wpdb->get_var( $countQuery );

		$this->table_data = $wpdb->get_results( $dataQuery, ARRAY_A );

		foreach ( $this->table_data as $row ) {
			$user   = get_user_by( 'id', $row['USER_ID'] );
			$data[] = array(
					'year'          => $row['year'],
					'month'         => $row['month'],
					'user'          => sprintf(
							'<a href="%s">%s-%s %s</a>',
							add_query_arg( 'user_id', $row['USER_ID'], self_admin_url( 'user-edit.php' ) ),
							$user->nickname,
							$user->first_name,
							$user->last_name
					),
					'product'       => $row['product_name'],
					'target'        => $row['target'],
					'current_state' => $row['total'] ?? __( 'Not Set', 'cbd-information-analyzer-textdomain' ),
					'updatedAt'     => $row['updatedAt'],
			);
		}

		$this->items = $data;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args( compact( 'total_items', 'per_page', 'total_pages' ) );
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
	}

	public function get_columns() {
		$columns = array(
				'year'          => __( 'Year', 'cbd-information-analyzer-textdomain' ),
				'month'         => __( 'Month', 'cbd-information-analyzer-textdomain' ),
				'user'          => __( 'User', 'cbd-information-analyzer-textdomain' ),
				'product'       => __( 'Product', 'cbd-information-analyzer-textdomain' ),
				'target'        => __( 'Target', 'cbd-information-analyzer-textdomain' ),
				'current_state' => __( 'Actual', 'cbd-information-analyzer-textdomain' ),
				'updatedAt'     => __( 'Updated At', 'cbd-information-analyzer-textdomain' ),
				'chart'         => __( 'Target Progress', 'cbd-information-analyzer-textdomain' ),
		);

		return $columns;
	}

	public function get_sortable_columns(): array {
		return array(
				'year'          => [ 'target_year', 'desc' ],
				'month'         => [ 'target_month', 'desc' ],
				'user'          => [ 'user_nicename', true ],
				'product'       => [ 'name', false ],
				'target'        => [ 'sut.amount', false ],
				'current_state' => [ 'total', false ],
				'updatedAt'     => [ 'updatedAt', false ]
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'cb':
			case 'year':
			case 'month':
			case 'user':
			case 'product':
			case 'target':
			case 'current_state':
			case 'updatedAt':
				return $item[ $column_name ];
			case 'target-actual':
			case 'chart':
				return $this->chart_column( $item );
			default:
				return print_r( $item, true );
		}
	}

	public function chart_column( $item ) {
		return '<canvas class="target-actual-progress" data-target="' . $item['target'] . '"  data-actual="' . $item['current_state'] . '" ></canvas>';
	}

	public function extra_tablenav( $which ) {
		global $wpdb;
		$table_name      = $wpdb->prefix . CbdInformationAnalyzerDatabase::SKU_USER_TARGET;
		$year_month_list = $wpdb->get_results( $wpdb->prepare( "SELECT target_year,target_month,concat(target_year,' ', MONTHNAME(STR_TO_DATE(target_month, '%m'))) as available_dates
FROM $table_name
group by target_year, target_month;" ) );

		if ( "top" === $which ) {
			?>
			<div class="alignleft actions bulkactions">
				<label class="hidden" for="filter-by-date-range"><?php
					_e( 'Select Date Range', 'cbd-information-analyzer-textdomain' ) ?></label><select
						name="filter-date-range" id="filter-by-date-range">
					<option value="0"><?php
						_e( 'All Times', 'cbd-information-analyzer-textdomain' ) ?></option>
					<?php
					foreach ( $year_month_list as $item ): ?>
						<?php
						$id       = $item->target_year . '-' . $item->target_month;
						$selected = ( $_REQUEST['filter-date-range'] === $id ) ? ' selected' : '';
						?>
						<option value="<?= $id ?>"<?= $selected ?>><?= $item->available_dates ?></option>
					<?php
					endforeach; ?>
				</select>
				<input type="submit" id="search-submit" class="button" value="<?php
				_e( 'Select Date Range', 'cbd-information-analyzer-textdomain' ) ?>">
			</div>
			<?php
		}
	}

}
