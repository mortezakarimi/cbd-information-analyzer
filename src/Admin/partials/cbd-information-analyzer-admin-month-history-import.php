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

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\services\UserHistoryService;
use Cbd_Information_Analyzer\Admin\tables\User_History_List_Table;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;

if ( ! current_user_can( 'add_actual' ) ) {
	CbdInformationAnalyzerUtilities::setErrors( 'Import User History',
			'You do not have permission to access this page.' );
}

$children = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
if ( ! empty( $children ) ) {
	UserHistoryService::handleImportUserTargetForm();
}

$history_list_table = new User_History_List_Table();

CbdInformationAnalyzerUtilities::showErrors( 'Import User History' );
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<div class="page-banner">
		<h1 class="wp-heading-inline"><?php
			_e( 'User History List', 'cbd-information-analyzer-textdomain' ) ?></h1>

		<hr class="wp-header-end">
		<form method="post">
			<?php
			$history_list_table->prepare_items();
			$history_list_table->search_box( __( 'Search', 'cbd-information-analyzer-textdomain' ),
					'search_id' );
			$history_list_table->display(); ?>
		</form>
	</div>
	<?php
	if ( ! empty( $children ) ): ?>
		<h1 id="add-new-user"><?php
			_e( 'Import user history Data', 'cbd-information-analyzer-textdomain' ) ?></h1>
		<div id="ajax-response"></div>
		<p id="add-new-user"><?php
			_e( 'You can import list of user history here', 'cbd-information-analyzer-textdomain' ) ?></p>
		<form method="post" enctype="multipart/form-data">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="user_historyfile"><?php
							_e( 'User History upload', 'cbd-information-analyzer-textdomain' ) ?></label>
					</th>
					<td>
						<input type="file" id="user_historyfile" name="user_historyfile" class="regular-text"/>
						<p class="description"><?php
							_e( 'Upload User History items as an excel file', 'cbd-information-analyzer-textdomain' ) ?>

							<a href="<?= admin_url( 'admin-post.php?action=download_history_example' ) ?>"><?php
								_e( 'Download Example file', 'cbd-information-analyzer-textdomain' ) ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<?php
						wp_nonce_field( 'cbd-analyzer-user_history-import',
								'cbd_information_analyzer_user_history_import_nonce' ); ?>
						<?php
						submit_button( __( 'Upload', 'cbd-information-analyzer-textdomain' ),
								'primary',
								'submit_import' ); ?>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	<?php
	endif; ?>
</div>
