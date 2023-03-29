<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserHistory;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
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
		$children    = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
		$spreadsheet = IOFactory::load( $filePath );
		$spreadsheet->setActiveSheetIndex( 0 );
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

			if ( null === $user || ! \in_array( $user->ID, $children, false ) ||
			     ! \in_array( strtolower( $user->get( 'position' ) ),
				     [ CbdInformationAnalyzerRoles::ROLE_CBD, CbdInformationAnalyzerRoles::ROLE_PR ],
				     false )
			) {
				continue;
			}
			$highestRow = $worksheet->getHighestRow();
			for ( $row = 2; $row <= $highestRow; ++ $row ) {
				$changeDate   = $worksheet->getCell( 'A' . $row )
				                          ->getValue();
				$productId    = $worksheet->getCell( 'B' . $row )
				                          ->getValue();
				$changeAmount = $worksheet->getCell( 'C' . $row )
				                          ->getValue();

				$changeDate = $changeDate ? new DateTime( $changeDate ) : new DateTime();
				$product    = Product::find( $productId );

				$userHistory = UserHistory::firstOrCreate( [
					'SKU_ID'   => $product->ID,
					'USER_ID'  => $user->ID,
					'changeAt' => $changeDate,
				], [
					'amount' => (int) $changeAmount,
				] );

				if ( $userHistory->exists && ! $userHistory->wasRecentlyCreated && current_user_can( 'manage_target' ) ) {
					$userHistory->amount = $changeAmount;
				}
				$userHistory->save();

				self::relatedHistories( $user, $product, $userHistory );
			}
		}
	}

	private static function relatedHistories( \WP_User $user, Product $product, UserHistory $user_history ): void {
		$parentUserID = get_user_meta( $user->ID, 'parent', true );
		$wp_user      = get_user_by( 'ID', $parentUserID );
		if ( ! empty( $parentUserID ) ) {
			$parentUser = User::find( $parentUserID );
			if ( $parentUser ) {
				$result      = self::calculateUserHistoriesDateByUser( $parentUser,
					$user_history->changeAt instanceof DateTime ? $user_history->changeAt : new DateTime( $user_history->changeAt ),
					$product->ID );
				$userHistory = UserHistory::firstOrCreate( [
					'SKU_ID'   => $product->ID,
					'USER_ID'  => $parentUser->ID,
					'changeAt' => $user_history->changeAt,
				], [
					'amount' => 0,
				] );
				do_action( 'qm/debug', $result );
				$userHistory->amount = $result->daily_actual ?? 0;
				$userHistory->save();
				self::relatedHistories( $wp_user, $product, $userHistory );
			}
		}
	}

	/**
	 * @param User $user
	 * @param DateTime $changeAt
	 * @param int|null $forProduct
	 *
	 * @return UserHistory
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function calculateUserHistoriesDateByUser(
		User $user,
		DateTime $changeAt,
		int $forProduct = null
	): UserHistory {
		$children = CbdInformationAnalyzerAdmin::get_child_users( $user->ID );

		$qb = UserHistory::whereIn( 'USER_ID', $children )
		                 ->where( 'changeAt', $changeAt );
		if ( $forProduct ) {
			$qb->where( 'SKU_ID', '=', $forProduct )
			   ->groupBy( [ 'SKU_ID' ] );
		}
		$qb
			->selectRaw( 'sum(amount) daily_actual' );

		return $qb->get()->first();
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

	public static function calculateTargetActualByProduct( int $user_id, int $product_id, int $month, int $year ) {
		return [
			'actual' =>
				UserHistory::query()
				           ->selectRaw( 'SUM(amount) as s' )
				           ->selectRaw( 'concat(USER_ID, SKU_ID, MONTH(changeAt), YEAR(changeAt)) as a' )
				           ->where( 'SKU_ID', '=', $product_id )
				           ->where( 'USER_ID', '=', $user_id )
				           ->whereMonth( 'changeAt', '=', $month )
				           ->whereYear( 'changeAt', '=', $year )
				           ->groupBy( [
					           'a'
				           ] )
				           ->value( 's' ),
			'target' => UserTarget::query()
			                      ->where( 'SKU_ID', '=', $product_id )
			                      ->where( 'USER_ID', '=', $user_id )
			                      ->where( 'target_month', '=', $month )
			                      ->where( 'target_year', '=', $year )
			                      ->value( 'amount' ),
		];
	}
}
