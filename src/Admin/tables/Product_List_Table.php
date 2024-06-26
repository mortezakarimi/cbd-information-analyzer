<?php

namespace Cbd_Information_Analyzer\Admin\tables;

use Cbd_Information_Analyzer\Admin\models\Product;

class Product_List_Table extends \WP_List_Table {
	private $table_data;

	public function __construct() {
		parent::__construct( array(
			'singular' => 'product',
			'plural'   => 'products',
			'ajax'     => false
		) );
	}

	public function prepare_items() {
		$per_page = 10;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$primary  = 'ID';

		$data = array();

		$paged  = isset( $_REQUEST['paged'] ) ? max( 0, (int) $_REQUEST['paged'] - 1 ) : 0;
		$offset = $paged * $per_page;

		$orderby = $_REQUEST['orderby'] ?? 'ID';
		$order   = $_REQUEST['order'] ?? 'ASC';

		$search = $_POST['s'] ?? null;

		$qb = Product::query();
		if ( ! empty( $search ) ) {
			$qb = $qb
				->where( 'name', 'LIKE', "%$search%" )
				->orWhere( 'ID', 'LIKE', "%$search%" );
		}

		$total_items      = $qb->count();
		$this->table_data = $qb->orderBy( $orderby, $order )
		                       ->offset( $offset )
		                       ->limit( $per_page )
		                       ->get()
		                       ->all();

		foreach ( $this->table_data as $row ) {
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
