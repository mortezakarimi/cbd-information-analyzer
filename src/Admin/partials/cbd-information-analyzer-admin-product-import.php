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

use Cbd_Information_Analyzer\Admin\partials\Product_List_Table;
use Cbd_Information_Analyzer\Admin\services\ProductService;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;


if ( ! current_user_can( 'manage_options' ) ) {
	CbdInformationAnalyzerUtilities::setErrors( 'Import Products', 'You do not have permission to access this page.' );
}
ProductService::handleImportProductForm();


$product_list_table = new Product_List_Table();

CbdInformationAnalyzerUtilities::showErrors( 'Import Products' );
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<div class="page-banner">
		<h1 class="wp-heading-inline"><?php
			_e( 'Product List', 'cbd-information-analyzer-textdomain' ) ?></h1>

		<hr class="wp-header-end">
		<form method="post">
			<?php
			$product_list_table->prepare_items();
			$product_list_table->search_box( __( 'Search', 'cbd-information-analyzer-textdomain' ),
					'search_id' );
			$product_list_table->display(); ?>
		</form>
	</div>
	<h1 id="add-new-user"><?php
		_e( 'Import Product Data', 'cbd-information-analyzer-textdomain' ) ?></h1>
	<div id="ajax-response"></div>
	<p id="add-new-user"><?php
		_e( 'You can import list of products here', 'cbd-information-analyzer-textdomain' ) ?></p>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="productsfile"><?php
						_e( 'SKU list upload', 'cbd-information-analyzer-textdomain' ) ?></label>
				</th>
				<td>
					<input type="file" id="productsfile" name="productsfile" class="regular-text"/>
					<p class="description"><?php
						_e( 'Upload SKU items as an excel file', 'cbd-information-analyzer-textdomain' ) ?>

						<a href="<?= admin_url( 'admin-post.php?action=download_products_example' ) ?>"><?php
							_e( 'Download Example file', 'cbd-information-analyzer-textdomain' ) ?></a>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<?php
					wp_nonce_field( 'cbd-analyzer-sku-import', 'cbd_information_analyzer_sku_import_nonce' ); ?>
					<?php
					submit_button( __( 'Upload', 'cbd-information-analyzer-textdomain' ),
							'primary',
							'submit_import' ); ?>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
</div>
