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

use Cbd_Information_Analyzer\Admin\services\UserRolePermissionService;

UserRolePermissionService::handleImportUserRelationForm();

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
	<h1 id="add-new-user"><?php
		_e( 'Import User Relation Data', 'cbd-information-analyzer-textdomain' ) ?></h1>
	<div id="ajax-response"></div>
	<p id="add-new-user"><?php
		_e( 'You can import list of users here', 'cbd-information-analyzer-textdomain' ) ?></p>
	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="usersfile"><?php
						_e( 'Users Relation list upload', 'cbd-information-analyzer-textdomain' ) ?></label>
				</th>
				<td>
					<input type="file" id="usersfile" name="usersfile" class="regular-text"/>
					<p class="description"><?php
						_e( 'Upload Users Relation items as an excel file', 'cbd-information-analyzer-textdomain' ) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<?php
					wp_nonce_field( 'cbd-analyzer-user_relation-import',
							'cbd_information_analyzer_user_relation_import_nonce' ); ?>
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
