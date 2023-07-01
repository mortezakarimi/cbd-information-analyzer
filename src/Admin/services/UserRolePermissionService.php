<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * SkuService
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 */
class UserRolePermissionService {
	public static function handleImportUserRelationForm() {
		if ( isset( $_POST['submit_import'] ) && wp_verify_nonce( $_POST['cbd_information_analyzer_user_relation_import_nonce'],
				'cbd-analyzer-user_relation-import' ) ) {
			if ( ! empty( $_FILES['usersfile']['name'] ) ) {
				$file = $_FILES['usersfile'];

				// Check file type and extension
				$filetype      = wp_check_filetype( $file['name'] );
				$allowed_types = [ 'xlsx' ]; // Only allow image files
				if ( \in_array( $filetype['ext'], $allowed_types, true ) ) {
					// Check file size
					$max_size = 1024 * 1024 * 100; // 100 MB
					if ( $file['size'] > $max_size ) {
						CbdInformationAnalyzerUtilities::setErrors( 'Import User Relation', 'File is too large' );
					} else {
						self::importUsersRelation( $file['tmp_name'] );
						wp_redirect(self_admin_url('admin.php?page=cbd-analyzer-history-import'));
					}
				} else {
					CbdInformationAnalyzerUtilities::setErrors( 'Import User Relation', 'Invalid file type' );
				}
			} else {
				CbdInformationAnalyzerUtilities::setErrors( 'Import User Relation',
					'Please select a file for upload' );
			}
		}
	}

	protected static function importUsersRelation( string $filePath ) {
		$spreadsheet = IOFactory::load( $filePath );
		$worksheet   = $spreadsheet->getActiveSheet()->setAutoFilter(
			$spreadsheet->getActiveSheet()
			            ->calculateWorksheetDimension()
		);
		$highestRow  = $worksheet->getHighestRow();
		for ( $row = 2; $row <= $highestRow; ++ $row ) {
			$personalCode = $worksheet->getCell( 'B' . $row )
			                          ->getValue();
			$position     = strtolower($worksheet->getCell( 'D' . $row )
			                          ->getValue());
			$area         = strtolower($worksheet->getCell( 'E' . $row )
			                          ->getValue());
			$region       = strtolower($worksheet->getCell( 'F' . $row )
			                          ->getValue());
			$parent       = strtolower($worksheet->getCell( 'G' . $row )
			                          ->getValue());
			if ( empty( $parent ) ) {
				$parent = strtolower($worksheet->getCell( 'H' . $row )
				                    ->getValue());
			}
			if ( empty( $parent ) ) {
				$parent = strtolower($worksheet->getCell( 'I' . $row )
				                    ->getValue());
			}


			$user = get_user_by( 'login', $personalCode );
			if ( $user ) {
				$parent = get_user_by( 'login', $parent );

				$cache_key = 'child_users_for_user_' . $user->ID;
				delete_transient($cache_key);

				if ( ! empty( $parent ) ) {
					update_user_meta(
						$user->ID,
						'parent',
						$parent->ID
					);
				}


				update_user_meta(
					$user->ID,
					'position',
					$position
				);

				$user->add_role(  $position );

				update_user_meta(
					$user->ID,
					'area',
					$area
				);

				update_user_meta(
					$user->ID,
					'region',
					$region
				);
			}
		}
	}
}
