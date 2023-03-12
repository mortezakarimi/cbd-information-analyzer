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

use Cbd_Information_Analyzer\Admin\partials\User_Target_List_Table;
use Cbd_Information_Analyzer\Admin\services\ProductService;
use Cbd_Information_Analyzer\Admin\services\UserTargetService;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;


if ( ! current_user_can( 'manage_options' ) ) {
	CbdInformationAnalyzerUtilities::setErrors( 'Import User Monthly Target', 'You do not have permission to access this page.' );
}
UserTargetService::handleImportUserTargetForm();


$monthly_target_list_table = new User_Target_List_Table();

CbdInformationAnalyzerUtilities::showErrors( 'Import User Monthly Target' );
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <div class="page-banner">
        <h1 class="wp-heading-inline"><?php
			_e( 'Monthly Target List', 'cbd-information-analyzer-textdomain' ) ?></h1>

        <hr class="wp-header-end">
        <form method="post">
			<?php
			$monthly_target_list_table->prepare_items();
			$monthly_target_list_table->search_box( __( 'Search', 'cbd-information-analyzer-textdomain' ),
				'search_id' );
			$monthly_target_list_table->display(); ?>
        </form>
    </div>
    <h1 id="add-new-user"><?php
		_e( 'Import user target Data', 'cbd-information-analyzer-textdomain' ) ?></h1>
    <div id="ajax-response"></div>
    <p id="add-new-user"><?php
		_e( 'You can import list of user target here', 'cbd-information-analyzer-textdomain' ) ?></p>
    <form method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="user_targetfile"><?php
						_e( 'User Target upload', 'cbd-information-analyzer-textdomain' ) ?></label>
                </th>
                <td>
                    <input type="file" id="user_targetfile" name="user_targetfile" class="regular-text"/>
                    <p class="description"><?php
						_e( 'Upload User Monthly Target items as an excel file', 'cbd-information-analyzer-textdomain' ) ?>

                        <a href="<?= admin_url( 'admin-post.php?action=download_monthly_target_example' ) ?>"><?php
							_e( 'Download Example file', 'cbd-information-analyzer-textdomain' ) ?></a>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td>
					<?php
					wp_nonce_field( 'cbd-analyzer-user_target-import', 'cbd_information_analyzer_user_target_import_nonce' ); ?>
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
