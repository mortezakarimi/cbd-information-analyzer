<?php

namespace Cbd_Information_Analyzer\Admin\models;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use WeDevs\ORM\WP\UserMeta;

/**
 * User
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 * @property int $ID
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property DateTime $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property string $display_name
 * @property UserMeta $meta
 * @property UserTarget[] $userTargets
 * @property UserHistory[] $userHistories
 */
class User extends \WeDevs\ORM\WP\User {

	public function userTargets(): HasMany {
		return $this->hasMany( UserTarget::class, 'USER_ID', 'ID' );
	}

	public function userHistories(): HasMany {
		return $this->hasMany( UserHistory::class, 'USER_ID', 'ID' );
	}

	public function scopeWithUserHistories( Builder $query ): void {
		$history = new UserHistory();
		$query->leftjoin( $history->getTable() . ' as uh', 'uh.USER_ID', '=', $this->getTable() . '.ID' );
	}

	public function scopeWithUserTargets( Builder $query ): void {
		$target = new UserTarget();
		$query->leftjoin( $target->getTable() . ' as ut', 'ut.USER_ID', '=', $this->getTable() . '.ID' );
	}

	public function scopeGroupByUser( Builder $query ): void {
		$query->groupBy( [ 'ID' ] );
	}

	public function scopeGroupUserTargetsByMonthYear(
		Builder $query,
		int $targetYear,
		int $targetMonth
	): void {
		$query->groupBy( [ 'ut.USER_ID', 'ut.target_month', 'ut.target_year' ] )
		      ->where( 'ut.target_month', '=', $targetMonth )
		      ->where( 'ut.target_year', '=', $targetYear )
		      ->selectRaw( 'sum(ut.amount) target_sum' )
		      ->selectRaw( 'sum(ut.actual) actual_sum' );
	}
}
