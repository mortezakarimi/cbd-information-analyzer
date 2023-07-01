<?php

namespace Cbd_Information_Analyzer\Admin\tables;

use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
use WeDevs\ORM\WP\UserMeta;

class User_Target_List_Table extends AbstractBaseHistoryTables {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'month-target',
			'plural'   => 'month-targets',
			'ajax'     => false
		) );
	}

	public function prepare_items(): void {
		[
			$columns,
			$hidden,
			$sortable,
			$primary,
			$year,
			$month
		] = $this->processTableData();
		$targetQueryBuilder = UserTarget::groupBy( [
			'USER_ID',
			'target_month',
			'target_year'
		] )
		                                ->where( 'target_month', $month )
		                                ->where( 'target_year', $year )
		                                ->selectRaw( 'sum(amount) target_sum' )
		                                ->selectRaw( 'sum(actual) actual_sum' )
		                                ->addSelect( 'total_working_days' )
		                                ->addSelect( 'USER_ID' )
		                                ->addSelect( 'target_month' )
		                                ->addSelect( 'target_year' );

		$qb = User::select( '*' )
		          ->whereIn( 'ID', $this->children )
		          ->leftJoinSub( $targetQueryBuilder, 'ut', 'ut.USER_ID', '=', 'ID' );


		$this->processPositionSearch( $qb );
		$this->processAreaSearch( $qb );
		$this->processNormalSearch( $qb );


		$this->processOrderBy( $qb );
		$this->set_pagination_args( $this->processPagination( $qb ) );
		$table_data = $qb->get()->all();
		$data       = $this->setTableDataToColumns( $table_data, $month, $year );

		$this->items           = $data;
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
	}


	/**
	 * @param array $table_data
	 * @param int $month
	 * @param int $year
	 *
	 * @return array
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function setTableDataToColumns( array $table_data, $month, $year ): array {
		$data = [];
		foreach ( $table_data as $userWithTarget ) {
			/** @var UserMeta $metaPosition */
			$metaPosition = $userWithTarget->meta()->where( [ 'meta_key' => 'position' ] )->first();
			/** @var UserMeta $metaArea */
			$metaArea = $userWithTarget->meta()->where( [ 'meta_key' => 'area' ] )->first();
			$wp_user  = get_user_by( 'id', $userWithTarget->ID );
			$data[]   = array(
				'month'              => $month,
				'year'               => $year,
				'user_id'            => $userWithTarget->ID,
				'user'               => sprintf( '<a href="%s">%s-%s %s</a>',
					add_query_arg( 'user_id', $userWithTarget->ID, self_admin_url( 'user-edit.php' ) ),
					$wp_user->nickname,
					$wp_user->first_name,
					$wp_user->last_name ),
				'user_type'          => $meta->meta_value ? strtoupper( $meta->meta_value ) : __( 'Not Set',
					'cbd-information-analyzer-textdomain' ),
				'user_area'          => $metaArea->meta_value ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'target'             => $userWithTarget->target_sum ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'actual'             => $userWithTarget->actual_sum ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'total_working_days' => $userWithTarget->total_working_days ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'createdAt'          => $userWithTarget->createdAt ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'updatedAt'          => $userWithTarget->updatedAt ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
			);
		}

		return $data;
	}

	public function get_columns(): array {
		return array(
			'user'               => __( 'User', 'cbd-information-analyzer-textdomain' ),
			'user_type'          => __( 'User Position', 'cbd-information-analyzer-textdomain' ),
			'user_area'          => __( 'User Area', 'cbd-information-analyzer-textdomain' ),
			'target'             => __( 'Target', 'cbd-information-analyzer-textdomain' ),
			'actual'             => __( 'Actual', 'cbd-information-analyzer-textdomain' ),
			'total_working_days' => __( 'Total Working days', 'cbd-information-analyzer-textdomain' ),
			'actions'            => __( 'Actions', 'cbd-information-analyzer-textdomain' ),
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'user'               => [ 'user_nicename', true ],
			'target'             => [ 'target_sum', false ],
			'actual'             => [ 'actual_sum', false ],
			'total_working_days' => [ 'total_working_days', false ],
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user':
			case 'user_type':
			case 'user_area':
			case 'target':
			case 'actual':
			case 'total_working_days':
			case 'actions':
				return $item[ $column_name ];
			default:
				return '';
		}
	}

}
