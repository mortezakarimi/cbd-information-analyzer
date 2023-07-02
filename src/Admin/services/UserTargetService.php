<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Admin\CbdInformationAnalyzerAdmin;
use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\models\User;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
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
	/**
	 * @throws Exception
	 */
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

	/**
	 * @throws Exception
	 */
	protected static function importUsersMonthlyTarget( string $filePath ): void {
		$children = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );
		$reader   = IOFactory::createReaderForFile( $filePath );
		$reader->setReadDataOnly( true );
		// Set the chunk size (number of rows to read at once)
		$spreadsheet = $reader->load( $filePath );
		$spreadsheet->setActiveSheetIndex( 0 );
		$totalProducts = Product::query()->count();
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

			$highestRow = $worksheet->getHighestRow() < ( $totalProducts + 1 ) ? $worksheet->getHighestRow() : ( $totalProducts + 1 );

			// Read the data from the start row to end row
			$rows = $worksheet->rangeToArray( "A2:F$highestRow" );
			foreach ( $rows as [$monthYear, $totalWorkingDays, $productId, $_, $target, $finalActual] ) {
				[ $year, $month ] = explode( '-', $monthYear, 2 );

				$product = Product::find( $productId );

				$userTarget = UserTarget::firstOrCreate( [
					'SKU_ID'       => $product->ID,
					'USER_ID'      => $user->ID,
					'target_year'  => (int) $year,
					'target_month' => (int) $month
				], [
					'total_working_days' => (int) $totalWorkingDays,
					'amount'             => (int) $target,
				] );
				if ( null === $userTarget->actual && current_user_can( 'add_final_actual' ) ) {
					$userTarget->actual = $finalActual;
				}
				if ( $userTarget->exists && ! $userTarget->wasRecentlyCreated && current_user_can( 'manage_target' ) ) {
					$userTarget->total_working_days = $totalWorkingDays;
					$userTarget->amount             = $target;
					$userTarget->actual             = $finalActual;
				}
				$userTarget->save();

				self::relatedTargets( $user, $product, $userTarget );
			}
		}
	}

	private static function relatedTargets( \WP_User $user, Product $product, UserTarget $user_target ): void {
		$parentUserID = get_user_meta( $user->ID, 'parent', true );
		$wp_user      = get_user_by( 'ID', $parentUserID );
		if ( ! empty( $parentUserID ) ) {
			$parentUser = User::find( $parentUserID );
			if ( $parentUser ) {
				$result     = self::calculateUserTargetsDateByUser( $parentUser,
					$user_target->target_year,
					$user_target->target_month,
					$product->ID );
				$userTarget = UserTarget::firstOrCreate( [
					'SKU_ID'       => $product->ID,
					'USER_ID'      => $parentUser->ID,
					'target_year'  => $user_target->target_year,
					'target_month' => $user_target->target_month
				], [
					'total_working_days' => $user_target->total_working_days,
					'amount'             => $user_target->amount,
					'actual'             => $user_target->actual
				] );
				if ( $userTarget->exists && ! $userTarget->wasRecentlyCreated ) {
					$userTarget->amount = $result->target_sum ?? 0;
					$userTarget->actual = $result->actual_sum ?? 0;
				}
				$userTarget->save();
				self::relatedTargets( $wp_user, $product, $userTarget );
			}
		}
	}

	/**
	 * @param User $user
	 * @param int $targetYear
	 *
	 * @param int $targetMonth
	 * @param int|null $forProduct
	 *
	 * @return UserTarget|null ({target_sum: int, actual_sum:int}&UserTarget)|null
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function calculateUserTargetsDateByUser(
		User $user,
		int $targetYear,
		int $targetMonth,
		int $forProduct = null
	): ?UserTarget {
		$children = CbdInformationAnalyzerAdmin::get_child_users( $user->ID );

		$qb = UserTarget::whereIn( 'USER_ID', $children )
		                ->groupBy( [ 'target_month', 'target_year' ] )
		                ->where( 'target_month', $targetMonth )
		                ->where( 'target_year', $targetYear );
		if ( $forProduct ) {
			$qb->where( 'SKU_ID', '=', $forProduct )
			   ->groupBy( [ 'SKU_ID' ] );
		}
		$qb
			->select( 'total_working_days' )
			->addSelect( 'updatedAt' )
			->addSelect( 'createdAt' )
			->selectRaw( 'sum(amount) target_sum' )
			->selectRaw( 'sum(actual) actual_sum' );

		return $qb->get()->first();
	}

	/**
	 * @throws Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public static function handleGenerateExample(): void {
		$qb        = Product::query();
		$updatedAt = $qb->max( 'updatedAt' );
		/** @var Product[] $products */
		$products = $qb->get()->all();

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


		$children = CbdInformationAnalyzerAdmin::get_child_users( get_current_user_id() );

		$dateTimeNow      = new DateTime();
		$totalWorkingDays = self::totalWorkingDays( $dateTimeNow );
		if ( ! empty( $children ) ) {
			/** @var \WP_User[] $users */
			$users       = get_users( [
				'role__in' => [ CbdInformationAnalyzerRoles::ROLE_CBD, CbdInformationAnalyzerRoles::ROLE_PR ],
				'include'  => $children
			] );
			$sheetNumber = 0;
			foreach ( $users as $user ) {
				$sheet = $spreadsheet->createSheet( $sheetNumber ++ );
				$sheet
					->setTitle( $user->user_login )
					->setCellValue( 'A1', 'Year-Month' )
					->setCellValue( 'B1', 'Total Working Days' )
					->setCellValue( 'C1', 'Product ID' )
					->setCellValue( 'D1', 'Product Name' )
					->setCellValue( 'E1', 'Target' );
				if ( current_user_can( 'add_final_actual' ) ) {
					$sheet->setCellValue( 'F1', 'Final Actual' );
				}

				$headerStyle = [
					'font'      => [
						'bold' => true,
					],
					'alignment' => [
						'horizontal' => Alignment::HORIZONTAL_CENTER,
					],
				];
				$sheet->getStyle( 'A1:F1' )->applyFromArray( $headerStyle );
				$rowNumber = 2;
				foreach ( $products as $product ) {
					$sheet->setCellValue( 'A' . $rowNumber, $dateTimeNow->format( 'Y-m' ) )
					      ->setCellValue( 'B' . $rowNumber, $totalWorkingDays )
					      ->setCellValue( 'C' . $rowNumber, $product->ID )
					      ->setCellValue( 'D' . $rowNumber, $product->name )
					      ->setCellValue( 'E' . $rowNumber, 0 );
					if ( current_user_can( 'add_final_actual' ) ) {
						$sheet->setCellValue( 'F' . $rowNumber, null );
					}

					$sheet->getStyle( 'A' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_DATE_MYMINUS );
					$sheet->getStyle( 'B' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
					$sheet->getStyle( 'C' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
					$sheet->getStyle( 'E' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );

					if ( current_user_can( 'add_final_actual' ) ) {
						$sheet->getStyle( 'F' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
					}
					++ $rowNumber;
				}
				$sheet->getColumnDimension( 'A' )->setAutoSize( true );
				$sheet->getColumnDimension( 'B' )->setAutoSize( true );
				$sheet->getColumnDimension( 'C' )->setAutoSize( true );
				$sheet->getColumnDimension( 'D' )->setAutoSize( true );
				$sheet->getColumnDimension( 'E' )->setAutoSize( true );

				if ( current_user_can( 'add_final_actual' ) ) {
					$sheet->getColumnDimension( 'F' )->setAutoSize( true );
				}
				$sheet->freezePane( 'G2' );
			}
			$spreadsheet->removeSheetByIndex( $sheetNumber );
		}
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="Users ' . $dateTimeNow->format( 'Y-M' ) . ' Target List.xlsx"' );
		header( 'Cache-Control: max-age=0' );

		$writer = IOFactory::createWriter( $spreadsheet, IOFactory::WRITER_XLS );
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

	/**
	 * @return array|UserTarget[]
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function getTargetsGroupByMonthAndYears(): array {
		return UserTarget::query()
		                 ->groupBy( [ 'target_year', 'target_month' ] )
		                 ->selectRaw( 'concat(target_year,\' \', MONTHNAME(STR_TO_DATE(target_month, \'%m\'))) as available_dates' )
		                 ->addSelect( 'target_year' )
		                 ->addSelect( 'target_month' )
		                 ->orderBy( 'target_year', 'desc' )
		                 ->orderBy( 'target_month', 'desc' )
		                 ->get()->all();
	}
}
