<?php

namespace Cbd_Information_Analyzer\Admin\models;

use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDatabase;
use WeDevs\ORM\Eloquent\Model;

/**
 * Product
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 * @property int $ID
 * @property string $sku_name
 * @property string $name
 * @property string $group_name
 * @property \DateTime $createdAt
 * @property \DateTime $updatedAt
 */
class Product extends Model {
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
	protected $table = CbdInformationAnalyzerDatabase::PRODUCT_TABLE;

	/** Everything below this is best done in an abstract class that custom tables extend */
	/**
	 * Columns that can be edited - IE not primary key or timestamps if being used
	 */
	protected $fillable = [
		'sku_name',
		'name',
		'group_name'
	];
	/**
	 * Set primary key as ID, because WordPress
	 *
	 * @var string
	 */
	protected $primaryKey = 'ID';
	/**
	 * Make ID guarded -- without this ID doesn't save.
	 *
	 * @var string
	 */
	protected $guarded = [ 'ID' ];

	/**
	 * Overide parent method to make sure prefixing is correct.
	 *
	 * @return string
	 */
	public function getTable()
	{
		if(isset($this->table)) {
			$prefix =  $this->getConnection()->db->prefix;
			return 0 === strpos( $this->table, $prefix ) ? $this->table : $prefix . $this->table;
		}

		return parent::getTable();
	}
}
