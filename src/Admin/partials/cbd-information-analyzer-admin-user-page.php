<?php

/**
 * Provide a admin area view for the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @see       https://morteza-karimi.ir
 * @since      1.0.0
 * @package           Cbd_Information_Analyzer
 */

use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\services\UserHistoryService;

$user = get_user_by( 'id', $_REQUEST['element'] );
[ $orderBy, $sort ] = explode( '-', $_REQUEST['sort-items'] );
$products       = Product::all()->map( function ( Product $product ) use ( $user ) {
	$result                       = UserHistoryService::calculateTargetActualByProduct(
		$user->ID,
		$product->ID,
		$_REQUEST['month'],
		$_REQUEST['year']
	);
	$product['target']            = $result['target'] ?? 0;
	$product['actual']            = $result['actual'] ?? 0;
	$product['remaining']         = $product['target'] - $product['actual'];
	$product['remaining-percent'] = ( $product['remaining'] / $product['target'] ) * 100;

	return $product;
} )->sortBy( $orderBy, SORT_REGULAR, 'desc' === $sort );
$availableSorts = [
	'target-asc'     => 'Target Ascending',
	'target-desc'    => 'Target Descending',
	'actual-asc'     => 'Actual Ascending',
	'actual-desc'    => 'Actual Descending',
	'remaining-percent-asc'  => 'Remaining Ascending',
	'remaining-percent-desc' => 'Remaining Descending',
];
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <div class="page-banner">
        <h1 class="wp-heading-inline"><?php
			_e( 'User Report', 'cbd-information-analyzer-textdomain' ) ?></h1>
        <hr class="wp-header-end">
        <div>
            <label class="hidden" for="sort-items"><?php
				_e( 'Sort Items', 'cbd-information-analyzer-textdomain' ) ?></label>
            <form method="post">
                <select
                        name="sort-items" id="sort-items">
					<?php
					foreach ( $availableSorts as $id => $label ): ?>
						<?php
						$selected = ( isset( $_REQUEST['sort-items'] ) and $_REQUEST['sort-items'] === $id ) ? ' selected' : '';
						?>
                        <option value="<?= $id ?>"<?= $selected ?>><?= $label ?></option>
					<?php
					endforeach; ?>
                </select>

                <input type="submit" id="search-submit" class="button" value="<?php
				_e( 'Filter', 'cbd-information-analyzer-textdomain' ) ?>">
            </form>
        </div>
    </div>
    <table>
		<?php
		$index = 1;
		$total = count( $products );
		foreach (
			$products

			as $product
		): ?>
			<?php
			if ( 1 === $index % 4 && $index < $total ):
				?>
                <tr>
			<?php
			endif; ?>
            <td>
                <div style="position: relative; height:300px; width:300px">
                    <canvas class="target-actual-progress" data-target="<?= $product['target'] ?>"
                            data-actual="<?= $product['actual'] ?>"></canvas>
                </div>
                <p><?= $product->ID ?>-<?= $product->name ?></p>
            </td>
			<?php
			if ( 0 === $index % 4 ):
				?>
                </tr>
			<?php
			endif; ?>
			<?php
			++ $index;
		endforeach; ?>
    </table>
</div>
