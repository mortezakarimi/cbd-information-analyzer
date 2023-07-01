<?php

namespace Cbd_Information_Analyzer\Admin\tables;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserHistory;
use Illuminate\Database\Query\JoinClause;

class User_Details_List_Table extends \WP_List_Table {

	private \WP_User $user;

	public function __construct( \WP_User $user ) {
		parent::__construct( array(
			'singular' => 'user-detail',
			'plural'   => 'user-details',
			'ajax'     => false
		) );
		$this->user = $user;
	}

	public function prepare_items() {
		$children = CbdInformationAnalyzerAdmin::get_child_users( $this->user->ID );
		if ( empty( $children ) ) {
			$children = [ $this->user->ID ];
		}
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$primary  = 'ID';

		$data   = array();
		$paged  = isset( $_REQUEST['paged'] ) ? max( 0, (int) $_REQUEST['paged'] - 1 ) : 0;
		$offset = $paged * $per_page;

		$orderby            = $_REQUEST['orderby'] ?? 'ID';
		$order              = $_REQUEST['order'] ?? 'ASC';
		$month              = $_REQUEST['month'];
		$year               = $_REQUEST['year'];
		$search             = $_POST['s'] ?? null;
		$user               = User::find( $this->user->ID );
		$actualQueryBuilder = UserHistory::query()
		                                 ->addSelect( 'SKU_ID' )
		                                 ->addSelect( 'USER_ID' )
		                                 ->selectRaw( 'SUM(amount) as s' )
		                                 ->selectRaw( 'concat(USER_ID, SKU_ID, MONTH(changeAt), YEAR(changeAt)) as a' )
		                                 ->where( 'USER_ID', '=', $user->ID )
		                                 ->whereMonth( 'changeAt', '=', $month )
		                                 ->whereYear( 'changeAt', '=', $year )
		                                 ->groupBy( [
			                                 'a'
		                                 ] );
		$qb                 = Product::query()
		                             ->leftJoinSub( $actualQueryBuilder, 'ut',
			                             function ( JoinClause $clause ) use ( $user ) {
				                             $clause->on( 'ut.SKU_ID', '=', 'ID' );
			                             } )
		                             ->leftJoinSub( User::query()
		                                                ->selectSub( 'ID', 'u_id' ),
			                             'u',
			                             'u.u_id',
			                             '=',
			                             'ut.USER_ID' )
		                             ->whereIn( 'u_id', $children );
		if ( ! empty( $search ) ) {
			$qb = $qb
				->where( 'name', 'LIKE', "%$search%" )
				->orWhere( 'ID', 'LIKE', "%$search%" );
		}

		$total_items = $qb->count();
		$table_data  = $qb->orderBy( $orderby, $order )
		                  ->offset( $offset )
		                  ->limit( $per_page )
		                  ->get()
		                  ->all();

		foreach ( $table_data as $row ) {
			$data[] = array(
				'id'         => $row->ID,
				'name'       => $row->name,
				'group_name' => $row->group_name,
				'createdAt'  => $row->createdAt,
				'updatedAt'  => $row->updatedAt
			);
		}

		$this->items = $data;
		$total_pages = ceil( $total_items / $per_page );
		$this->set_pagination_args( compact( 'total_items', 'per_page', 'total_pages' ) );
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
	}


	public function get_columns() {
		return array(
			'id'         => __( 'ID', 'cbd-information-analyzer-textdomain' ),
			'name'       => __( 'Name', 'cbd-information-analyzer-textdomain' ),
			'group_name' => __( 'Group', 'cbd-information-analyzer-textdomain' ),
			'createdAt'  => __( 'Created At', 'cbd-information-analyzer-textdomain' ),
			'updatedAt'  => __( 'Updated At', 'cbd-information-analyzer-textdomain' ),
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'id'         => [ 'ID', 'asc' ],
			'name'       => [ 'name', false ],
			'group_name' => [ 'group_name', false ],
			'createdAt'  => [ 'createdAt', true ],
			'updatedAt'  => [ 'updatedAt', true ]
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'cb':
			case 'id':
			case 'name':
			case 'group_name':
			case 'createdAt':
			case 'updatedAt':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

}
