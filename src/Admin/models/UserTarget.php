<?php

namespace Cbd_Information_Analyzer\Admin\models;

use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDatabase;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use WeDevs\ORM\Eloquent\Model;
use WeDevs\ORM\WP\User;

/**
 * Product
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 * @property int $SKU_ID
 * @property string $USER_ID
 * @property int $target_year
 * @property int $target_month
 * @property int $total_working_days
 * @property int $amount
 * @property int $actual
 * @property DateTime $updatedAt
 * @property Product $product
 * @property User $user
 */
class UserTarget extends Model {
	/**
	 * The name of the "created at" column.
	 *
	 * @var string
	 */
	const CREATED_AT = 'createdAt';
	/**
	 * The name of the "updated at" column.
	 *
	 * @var string
	 */
	const UPDATED_AT = 'updatedAt';
	/**
	 * Disable created_at and update_at columns, unless you have those.
	 */
	public $timestamps = true;
	public $incrementing = false;
	/**
	 * Name for table without prefix
	 *
	 * @var string
	 */
	protected $table = CbdInformationAnalyzerDatabase::SKU_USER_TARGET;

	/** Everything below this is best done in an abstract class that custom tables extend */
	/**
	 * Columns that can be edited - IE not primary key or timestamps if being used
	 */
	protected $fillable = [
		'SKU_ID',
		'USER_ID',
		'target_year',
		'target_month',
		'total_working_days',
		'amount',
		'actual',
	];

	protected $primaryKey = false;

	/**
	 * Overide parent method to make sure prefixing is correct.
	 *
	 * @return string
	 */
	public function getTable(): string {
		if ( isset( $this->table ) ) {
			$prefix = $this->getConnection()->db->prefix;

			return 0 === strpos( $this->table, $prefix ) ? $this->table : $prefix . $this->table;
		}

		return parent::getTable();
	}

	/**
	 * Get the phone associated with the user.
	 * @return BelongsTo<Product>
	 */
	public function product(): BelongsTo {
		return $this->belongsTo( Product::class, 'SKU_ID', 'ID' )->withDefault();
	}

	/**
	 * Get the phone associated with the user.
	 */
	public function user(): BelongsTo {
		return $this->belongsTo( User::class, 'USER_ID', 'ID' )->withDefault();
	}

	protected function setKeysForSaveQuery( $query ): Builder {
		$query
			->where( 'USER_ID', '=', $this->getAttribute( 'USER_ID' ) )
			->where( 'SKU_ID', '=', $this->getAttribute( 'SKU_ID' ) )
			->where( 'target_year', '=', $this->getAttribute( 'target_year' ) )
			->where( 'target_month', '=', $this->getAttribute( 'target_month' ) );
		return $query;
	}
}
