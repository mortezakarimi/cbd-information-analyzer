<?php

namespace Cbd_Information_Analyzer\Admin\tables;

use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserHistory;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
use Illuminate\Database\Query\JoinClause;
use WeDevs\ORM\WP\UserMeta;

class User_History_List_Table extends AbstractBaseHistoryTables {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'month-history',
			'plural'   => 'month-histories',
			'ajax'     => false
		) );
	}

	public function prepare_items() {
		[
			$columns,
			$hidden,
			$sortable,
			$primary,
			$year,
			$month
		] = $this->processTableData();
		$historyQueryBuilder = UserHistory::groupBy( [ 'h_user_id', 'tmonth', 'tyear' ] )
		                                  ->selectRaw( 'USER_ID h_user_id' )
		                                  ->selectRaw( 'sum(amount) real_sum' )
		                                  ->selectRaw( 'month(changeAt) tmonth' )
		                                  ->selectRaw( 'year(changeAt)  tyear' );

		$targetQueryBuilder = UserTarget::groupBy( [
			'USER_ID',
			'target_month',
			'target_year'
		] )
		                                ->where( 'target_month', $month )
		                                ->where( 'target_year', $year )
		                                ->select( 'USER_ID' )
		                                ->selectRaw( 'sum(amount) target_sum' )
		                                ->selectRaw( 'sum(actual) actual_sum' )
		                                ->addSelect( 'total_working_days' )
		                                ->addSelect( 'target_month' )
		                                ->addSelect( 'target_year' )
		                                ->addSelect( 'uh.real_sum' )
		                                ->leftJoinSub( $historyQueryBuilder, 'uh', function ( JoinClause $join ) {
			                                $join->on( 'uh.h_user_id', '=', 'USER_ID' );
			                                $join->on( 'uh.tmonth', '=', 'target_month' );
			                                $join->on( 'uh.tyear', '=', 'target_year' );
		                                } );

		$qb = User::whereIn( 'ID', $this->children )
		          ->leftJoinSub( $targetQueryBuilder, 'ut', 'ut.USER_ID', '=', 'ID' );


		$this->processPositionSearch( $qb );
		$this->processNormalSearch( $qb );


		$this->processOrderBy( $qb );
		$this->set_pagination_args( $this->processPagination( $qb ) );
		$table_data = $qb->select( '*' )->get()->all();

		$data = $this->setTableDataToColumns( $table_data, $month, $year );

		$this->items           = $data;
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
	}


	/**
	 * @param User[] $table_data
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
			/** @var UserMeta $meta */
			$meta    = $userWithTarget->meta()->where( [ 'meta_key' => 'position' ] )->first();
			$wp_user = get_user_by( 'id', $userWithTarget->ID );
			$data[]  = array(
				'month'              => $month,
				'year'               => $year,
				'user_id'            => $userWithTarget->ID,
				'user'               => current_user_can( 'edit_user', $wp_user->ID ) ? sprintf( '<a href="%s">%s-%s %s</a>',
					add_query_arg( 'user_id', $userWithTarget->ID, self_admin_url( 'user-edit.php' ) ),
					$wp_user->nickname,
					$wp_user->first_name,
					$wp_user->last_name ) : sprintf( '%s-%s %s',
					$wp_user->nickname,
					$wp_user->first_name,
					$wp_user->last_name ),
				'user_type'          => $meta->meta_value ?strtoupper($meta->meta_value): __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'target'             => $userWithTarget->target_sum ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'current_state'      => $userWithTarget->real_sum ?? __( 'Not Set',
						'cbd-information-analyzer-textdomain' ),
				'total_elapsed_days' => (string) ( $userWithTarget
					                                   ->userHistories()->groupUserTargetsByMonthYear( $year,
						$month )->get()->count() ?? __( 'Not Set',
					'cbd-information-analyzer-textdomain' ) ),
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
			'user'                 => __( 'User', 'cbd-information-analyzer-textdomain' ),
			'user_type'            => __( 'User Position', 'cbd-information-analyzer-textdomain' ),
			'target'               => __( 'Target', 'cbd-information-analyzer-textdomain' ),
			'current_state'        => __( 'Actual', 'cbd-information-analyzer-textdomain' ),
			'total_elapsed_days'   => __( 'Total Elapsed days', 'cbd-information-analyzer-textdomain' ),
			'total_working_days'   => __( 'Total Working days', 'cbd-information-analyzer-textdomain' ),
			'prediction_for_month' => __( 'Achievement (Trend)', 'cbd-information-analyzer-textdomain' ),
			'chart'                => __( 'Status Chart', 'cbd-information-analyzer-textdomain' ),
			'actions'              => __( 'Actions', 'cbd-information-analyzer-textdomain' ),
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'user'               => [ 'user_nicename', true ],
			'target'             => [ 'target_sum', false ],
			'current_state'      => [ 'actual_sum', false ],
			'total_working_days' => [ 'total_working_days', false ],
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user':
			case 'user_type':
			case 'target':
			case 'current_state':
			case 'total_elapsed_days':
			case 'total_working_days':
			case 'actions':
			case 'chart':
				return $item[ $column_name ];
			case 'prediction_for_month':
				return $this->calculate_prediction_for_month( $item );
			default:
				return '';
		}
	}

	public function calculate_prediction_for_month( $item ) {
		try {
			if ( $item['total_elapsed_days'] && $item['total_working_days'] && $item['target'] ) {
				return sprintf( '%.2f%%',
					( $item['current_state'] / ( $item['total_elapsed_days'] / $item['total_working_days'] ) / $item['target'] ) * 100 );
			}
		} catch ( \Exception $exception ) {
		}

		return __( 'Not Set',
			'cbd-information-analyzer-textdomain' );
	}

	public function column_chart( $item ) {
		return '<canvas class="target-actual-progress" data-target="' . ( $item['target'] === __( 'Not Set',
				'cbd-information-analyzer-textdomain' ) ? 1 : $item['target'] ) . '"  data-actual="' . ( $item['current_state'] === __( 'Not Set',
				'cbd-information-analyzer-textdomain' ) ? 0 : $item['current_state'] ) . '" ></canvas>';
	}

}
