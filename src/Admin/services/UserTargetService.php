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
class UserTargetService {
	public static function handleImportUserTargetForm() {
		if ( isset( $_POST['submit_import'] ) && wp_verify_nonce( $_POST['cbd_information_analyzer_user_target_import_nonce'],
				'cbd-analyzer-user_target-import' ) ) {
			if ( ! empty( $_FILES['user_targetfile']['name'] ) ) {
				$file = $_FILES['user_targetfile'];

				// Check file type and extension
				$filetype      = wp_check_filetype( $file['name'] );
				$allowed_types = [ 'xlsx' ]; // Only allow image files
				if ( \in_array( $filetype['ext'], $allowed_types, true ) ) {
					// Check file size
					$max_size = 1024 * 1024 * 100; // 100 MB
					if ( $file['size'] > $max_size ) {
						CbdInformationAnalyzerUtilities::setErrors( 'Import User Monthly Target', 'File is too large' );
					} else {
						self::importUsersMonthlyTarget( $file['tmp_name'] );
					}
				} else {
					CbdInformationAnalyzerUtilities::setErrors( 'Import User Monthly Target', 'Invalid file type' );
				}
			} else {
				CbdInformationAnalyzerUtilities::setErrors( 'Import User Monthly Target',
					'Please select a file for upload' );
			}
		}
	}

	protected static function importUsersMonthlyTarget( string $filePath ) {
		global $wpdb;
		$userTargetTable = $wpdb->prefix . CbdInformationAnalyzerDatabase::SKU_USER_TARGET;
		$spreadsheet     = IOFactory::load( $filePath );

		foreach ( $spreadsheet->getWorksheetIterator() as $worksheet ) {
			$worksheet->setAutoFilter(
				$spreadsheet->getActiveSheet()
				            ->calculateWorksheetDimension()
			);
			$personalCode = $worksheet->getTitle();
			if ( ! is_numeric( $personalCode ) ) {
				continue;
			}
			$user = get_user_by( 'login', $personalCode );

			if ( ! \in_array( strtolower( $user->get( 'position' ) ), [ 'cbc', 'pr' ], false ) ) {
				continue;
			}
			$highestRow = $worksheet->getHighestRow();
			for ( $row = 2; $row <= $highestRow; ++ $row ) {
				$monthYear = $worksheet->getCell( 'A' . $row )
				                       ->getValue();
				[ $year, $month ] = explode( '-', $monthYear, 2 );
				$totalWorkingDays = $worksheet->getCell( 'B' . $row )
				                              ->getValue();
				$productId        = $worksheet->getCell( 'C' . $row )
				                              ->getValue();
				$target           = $worksheet->getCell( 'D' . $row )
				                              ->getValue();

				$isExist = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS(SELECT 1 FROM $userTargetTable WHERE `SKU_ID` = %d AND `USER_ID` = %d AND `target_year` = %d AND `target_month` = %d  LIMIT 1)",
					[ $productId, $user->ID, $year, $month ] ) );

				if ( ! $isExist ) {
					$wpdb->insert( $userTargetTable, [
						'SKU_ID'             => $productId,
						'USER_ID'            => $user->ID,
						'target_year'        => $year,
						'target_month'       => (int) $month,
						'total_working_days' => (int) $totalWorkingDays,
						'amount'             => $target,
						'createdAt'          => ( new DateTime() )->format( 'Y-m-d h:i:s' ),
						'updatedAt'          => ( new DateTime() )->format( 'Y-m-d h:i:s' )
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

		$spreadsheet = new Spreadsheet();
		$spreadsheet
			->getProperties()
			->setCreator( 'CBD Information Analyzer Wordpress Plugin' )
			->setLastModifiedBy( 'CBD Information Analyzer Wordpress Plugin' )
			->setCreated( time() )
			->setModified( $updatedAt )
			->setTitle( 'Example users monthly target import XLSX File' )
			->setSubject( 'Example users monthly target import XLSX File' )
			->setDescription( 'Example users monthly target import XLSX File' )
			->setKeywords( 'example, xlsx, users monthly target list' )
			->setCategory( 'Example Files' );


		/** @var User[] $users */
		$users            = get_users( [
			'role__in' => [ CbdInformationAnalyzerRoles::ROLE_CBD, CbdInformationAnalyzerRoles::ROLE_PR ],
			'include'  => CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() )
		] );
		$sheetNumber      = 0;
		$dateTimeNow      = new DateTime();
		$totalWorkingDays = self::totalWorkingDays( $dateTimeNow );
		foreach ( $users as $user ) {
			$sheet = $spreadsheet->createSheet( $sheetNumber ++ );
			$sheet
				->setTitle( $user->user_login )
				->setCellValue( 'A1', 'Year-Month' )
				->setCellValue( 'B1', 'Total Working Days' )
				->setCellValue( 'C1', 'Product ID' )
				->setCellValue( 'D1', 'Target' );

			$headerStyle = [
				'font'      => [
					'bold' => true,
				],
				'alignment' => [
					'horizontal' => Alignment::HORIZONTAL_CENTER,
				],
			];
			$sheet->getStyle( 'A1:D1' )->applyFromArray( $headerStyle );
			$rowNumber = 2;
			foreach ( $products as $product ) {
				$sheet->setCellValue( 'A' . $rowNumber, $dateTimeNow->format( 'Y-m' ) )
				      ->setCellValue( 'B' . $rowNumber, $totalWorkingDays )
				      ->setCellValue( 'C' . $rowNumber, $product->ID )
				      ->setCellValue( 'D' . $rowNumber, 0 );

				$sheet->getStyle( 'A' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_DATE_MYMINUS );
				$sheet->getStyle( 'B' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
				$sheet->getStyle( 'C' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
				$sheet->getStyle( 'D' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
				++ $rowNumber;
			}
			$sheet->getColumnDimension( 'A' )->setAutoSize( true );
			$sheet->getColumnDimension( 'B' )->setAutoSize( true );
			$sheet->getColumnDimension( 'C' )->setAutoSize( true );
			$sheet->getColumnDimension( 'D' )->setAutoSize( true );
			$sheet->freezePane( 'E2' );
		}
		$spreadsheet->removeSheetByIndex( $sheetNumber );
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="Users ' . $dateTimeNow->format( 'Y-M' ) . ' Target List.xlsx"' );
		header( 'Cache-Control: max-age=0' );

		$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
		$writer->save( 'php://output' );
		exit;
	}

	/**
	 * @param DateTime $dateTimeNow
	 *
	 * @return string
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function totalWorkingDays( DateTime $dateTimeNow ): string {
		$workdays  = array();
		$month     = $dateTimeNow->format( 'm' ); // Month ID, 1 through to 12.
		$year      = $dateTimeNow->format( 'Y' ); // Year in 4 digit 2009 format.
		$day_count = $dateTimeNow->format( 't' ); // Get the amount of days


		//loop through all days
		for ( $i = 1; $i <= $day_count; $i ++ ) {
			$date     = $year . '/' . $month . '/' . $i; //format date
			$get_name = date( 'l', strtotime( $date ) ); //get week day
			$day_name = substr( $get_name, 0, 3 ); // Trim day name to 3 chars

			//if not a weekend add day to array
			if ( 'Fri' !== $day_name ) {
				$workdays[] = $i;
			}
		}

		return count( $workdays );
	}
}
