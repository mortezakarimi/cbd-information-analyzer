<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDatabase;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerRoles;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;
use DateTime;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * SkuService
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 */
class UserHistoryService {
	public static function handleImportUserTargetForm() {
		if ( isset( $_POST['submit_import'] ) && wp_verify_nonce( $_POST['cbd_information_analyzer_user_history_import_nonce'],
				'cbd-analyzer-user_history-import' ) ) {
			if ( ! empty( $_FILES['user_historyfile']['name'] ) ) {
				$file = $_FILES['user_historyfile'];

				// Check file type and extension
				$filetype      = wp_check_filetype( $file['name'] );
				$allowed_types = [ 'xlsx' ]; // Only allow image files
				if ( \in_array( $filetype['ext'], $allowed_types, true ) ) {
					// Check file size
					$max_size = 1024 * 1024 * 100; // 100 MB
					if ( $file['size'] > $max_size ) {
						CbdInformationAnalyzerUtilities::setErrors( 'Import User History', 'File is too large' );
					} else {
						self::importUsersMonthlyTarget( $file['tmp_name'] );
					}
				} else {
					CbdInformationAnalyzerUtilities::setErrors( 'Import User History', 'Invalid file type' );
				}
			} else {
				CbdInformationAnalyzerUtilities::setErrors( 'Import User History',
					'Please select a file for upload' );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	protected static function importUsersMonthlyTarget( string $filePath ) {
		global $wpdb;
		$userHistoryTable = $wpdb->prefix . CbdInformationAnalyzerDatabase::SKU_USER_HISTORY;
		$spreadsheet      = IOFactory::load( $filePath );
		$children         = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
		$spreadsheet->setActiveSheetIndex( 0 );
		foreach ( $spreadsheet->getWorksheetIterator() as $worksheet ) {
			$personalCode = $worksheet->getTitle();

			if ( ! is_numeric( $personalCode ) ) {
				continue;
			}
			$user = get_user_by( 'login', $personalCode );

			if ( ! \in_array( $user->ID, $children, false ) ||
			     ! \in_array( strtolower( $user->get( 'position' ) ),
				     [ CbdInformationAnalyzerRoles::ROLE_CBD, CbdInformationAnalyzerRoles::ROLE_PR ],
				     false )
			) {
				continue;
			}
			$highestRow = $worksheet->getHighestRow();
			for ( $row = 2; $row <= $highestRow; ++ $row ) {
				$changeDate = $worksheet->getCell( 'A' . $row )
				                        ->getValue();
				$productId  = $worksheet->getCell( 'B' . $row )
				                        ->getValue();
				$changeAmount     = $worksheet->getCell( 'C' . $row )
				                        ->getValue();

				$isExist = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS(SELECT 1 FROM $userHistoryTable WHERE `SKU_ID` = %d AND `USER_ID` = %d AND `changeAt` = %s  LIMIT 1)",
					[ $productId, $user->ID, $changeDate ] ) );
				if ( ! $isExist ) {
					$wpdb->insert( $userHistoryTable, [
						'SKU_ID'    => $productId,
						'USER_ID'   => $user->ID,
						'changeAt'  => ( new DateTime( $changeDate ) )->format( 'Y-m-d' ),
						'amount'    => $changeAmount,
						'createdAt' => ( new DateTime() )->format( 'Y-m-d h:i:s' ),
						'updatedAt' => ( new DateTime() )->format( 'Y-m-d h:i:s' )
					] );
				}
			}
		}
	}

	/**
	 * @throws Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public static function handleGenerateExample(): void {
		global $wpdb;
		$productTable = $wpdb->prefix . CbdInformationAnalyzerDatabase::PRODUCT_TABLE;

		$updatedAt = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(updatedAt) FROM $productTable" ) );
		/** @var Product[] $products */
		$products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $productTable" ) );

		/** @var User[] $users */
		$users = get_users( [
			'role__in' => [ CbdInformationAnalyzerRoles::ROLE_CBD, CbdInformationAnalyzerRoles::ROLE_PR ],
			'include'  => CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() )
		] );

		$spreadsheet = new Spreadsheet();
		$spreadsheet
			->getProperties()
			->setCreator( 'CBD Information Analyzer Wordpress Plugin' )
			->setLastModifiedBy( 'CBD Information Analyzer Wordpress Plugin' )
			->setCreated( time() )
			->setModified( $updatedAt )
			->setTitle( 'Example users daily changes import XLSX File' )
			->setSubject( 'Example users daily changes import XLSX File' )
			->setDescription( 'Example users daily changes import XLSX File' )
			->setKeywords( 'example, xlsx, users daily changes list' )
			->setCategory( 'Example Files' );


		$sheetNumber = 0;

		$dateTimeNow = new DateTime();
		foreach ( $users as $user ) {
			$sheet = $spreadsheet->createSheet( $sheetNumber ++ );
			$sheet
				->setTitle( $user->user_login )
				->setCellValue( 'A1', 'Year-Month-Day' )
				->setCellValue( 'B1', 'Product ID' )
				->setCellValue( 'C1', 'Changes' );

			$headerStyle = [
				'font'      => [
					'bold' => true,
				],
				'alignment' => [
					'horizontal' => Alignment::HORIZONTAL_CENTER,
				],
			];
			$sheet->getStyle( 'A1:C1' )->applyFromArray( $headerStyle );
			$rowNumber = 2;
			foreach ( $products as $product ) {
				$sheet->setCellValue( 'A' . $rowNumber, $dateTimeNow->format( 'd-m-Y' ) )
				      ->setCellValue( 'B' . $rowNumber, $product->ID )
				      ->setCellValue( 'C' . $rowNumber, 0 );
				$sheet->getStyle( 'A' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_DATE_DMYMINUS );
				$sheet->getStyle( 'B' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
				$sheet->getStyle( 'C' . $rowNumber )->getNumberFormat()->setFormatCode( '+0;-0;0' );
				++ $rowNumber;
			}
		}
		$spreadsheet->removeSheetByIndex( $sheetNumber );
		$sheet->getColumnDimension( 'A' )->setAutoSize( true );
		$sheet->getColumnDimension( 'B' )->setAutoSize( true );
		$sheet->getColumnDimension( 'C' )->setAutoSize( true );
		$sheet->freezePane( 'D2' );
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="Users Daily Change for ' . $dateTimeNow->format( 'd-M-Y' ) . ' List.xlsx"' );
		header( 'Cache-Control: max-age=0' );

		$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
		$writer->save( 'php://output' );
		exit;
	}
}
