<?php

namespace Cbd_Information_Analyzer\Admin\tables;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
use Cbd_Information_Analyzer\Admin\services\UserTargetService;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerRoles;
use Illuminate\Database\Eloquent\Builder;
use WP_List_Table;
use WP_User;

abstract class AbstractBaseHistoryTables extends WP_List_Table {
	/** @var array|UserTarget[] */
	protected array $month_year_list;
	/**
	 * @var WP_User[]
	 */
	protected array $children;

	public function __construct( $args = array() ) {
		$this->month_year_list = UserTargetService::getTargetsGroupByMonthAndYears();

		$this->children = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
		parent::__construct( $args );
	}

	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
            <div class="alignleft actions bulkactions">
                <label class="hidden" for="filter-by-date-range"><?php
					_e( 'Select Date Range', 'cbd-information-analyzer-textdomain' ) ?></label>
                <select
                        name="filter-date-range" id="filter-by-date-range">
					<?php
					foreach ( $this->month_year_list as $item ): ?>
						<?php
						$id       = $item->target_year . '-' . $item->target_month;
						$selected = ( isset( $_REQUEST['filter-date-range'] ) and $_REQUEST['filter-date-range'] === $id ) ? ' selected' : '';
						?>
                        <option value="<?= $id ?>"<?= $selected ?>><?= $item->available_dates ?></option>
					<?php
					endforeach; ?>
                </select>
                <label class="hidden" for="filter-user-position"><?php
					_e( 'Select Position', 'cbd-information-analyzer-textdomain' ) ?></label>
                <select
                        name="filter-user-position" id="filter-user-position">
                    <option value=""><?php
						_e( 'All Positions', 'cbd-information-analyzer-textdomain' ); ?></option>
					<?php
					foreach ( CbdInformationAnalyzerRoles::getAvailableRoles() as $id => $title ): ?>
						<?php
						$selected = ( isset( $_REQUEST['filter-user-position'] ) and $_REQUEST['filter-user-position'] === $id ) ? ' selected' : '';
						?>
                        <option value="<?= $id ?>"<?= $selected ?>><?= $title ?></option>
					<?php
					endforeach; ?>
                </select>
                <input type="submit" id="search-submit" class="button" value="<?php
				_e( 'Filter', 'cbd-information-analyzer-textdomain' ) ?>">
            </div>
			<?php
		}
	}

	public function column_actions( $item ): string {
		$actions = array(
			'view' => sprintf( '<a href="?page=cbd-analyzer-user-report-page&element=%s&month=%s&year=%s">' . __( 'View Report',
					'cbd-information-analyzer-textdomain' ) . '</a>',
				$item['user_id'],
				$item['month'],
				$item['year'],
			),
		);

		return $this->row_actions( $actions, true );
	}


	/**
	 * @return array
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function processTableData(): array {
		$columns  = $this->get_columns();
		$hidden   = array( 'updatedAt', 'createdAt' );
		$sortable = $this->get_sortable_columns();
		$primary  = 'USER_ID';

		[ $year, $month ] = $this->calculateMonthYearFilter();

		return array(
			$columns,
			$hidden,
			$sortable,
			$primary,
			$year,
			$month
		);
	}

	/**
	 * @return array
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function calculateMonthYearFilter(): array {
		if ( ! isset( $_REQUEST['filter-date-range'] ) ) {
			$_REQUEST['filter-date-range'] = isset( $this->month_year_list[0] ) ? $this->month_year_list[0]->target_year . '-' . $this->month_year_list[0]->target_month : '0000-00';
		}

		/** @var array{0:int,1:int} $limit_date_range */
		[ $year, $month ] = explode( '-', $_REQUEST['filter-date-range'] );

		return array( $year, $month );
	}

	/**
	 * @param Builder|User $qb
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function processNormalSearch( Builder $qb ): void {
		$search = $_POST['s'] ?? null;
		if ( ! empty( $search ) ) {
			$qb->whereHas( 'user', function ( $query ) use ( $search ) {
				return $query->where( 'user_login', 'LIKE', "%$search%" );
			} );
		}
	}

	/**
	 * @param Builder|User $qb
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function processOrderBy( Builder $qb ): void {
		$orderby = $_REQUEST['orderby'] ?? 'ID';
		$order   = $_REQUEST['order'] ?? 'asc';
		$qb->orderBy( $orderby, $order );
	}

	/**
	 * @param Builder|User $qb
	 *
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function processPagination( Builder $qb ): array {
		$per_page    = 10;
		$total_items = $qb->select( 'ID' )->get()->count();
		$total_pages = ceil( $total_items / $per_page );

		$paged  = isset( $_REQUEST['paged'] ) ? max( 0, (int) $_REQUEST['paged'] - 1 ) : 0;
		$offset = $paged * $per_page;
		$qb->offset( $offset )->limit( $per_page );

		return compact( 'total_items', 'per_page', 'total_pages' );
	}

	/**
	 * @param Builder|User $qb
	 *
	 * @return void
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public function processPositionSearch( Builder $qb ): void {
		$positionSearch = $_REQUEST['filter-user-position'] ?? null;
		if ( ! empty( $positionSearch ) ) {
			$qb->whereHas( 'meta', function ( Builder $builder ) use ( $positionSearch ) {
				$builder->where( 'meta_key', '=', 'position' )->where( 'meta_value', '=', $positionSearch );
			} );
		}
	}

}
