<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerDatabase;
use Cbd_Information_Analyzer\Includes\CbdInformationAnalyzerUtilities;
use DateTime;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * SkuService
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 */
class ProductService {
	public static function handleImportProductForm() {
		if ( isset( $_POST['submit_import'] ) && wp_verify_nonce( $_POST['cbd_information_analyzer_sku_import_nonce'],
				'cbd-analyzer-sku-import' ) ) {
			if ( ! empty( $_FILES['productsfile']['name'] ) ) {
				$file = $_FILES['productsfile'];

				// Check file type and extension
				$filetype      = wp_check_filetype( $file['name'] );
				$allowed_types = [ 'xlsx' ]; // Only allow image files
				if ( in_array( $filetype['ext'], $allowed_types, true ) ) {
					// Check file size
					$max_size = 1024 * 1024; // 1 MB
					if ( $file['size'] > $max_size ) {
						CbdInformationAnalyzerUtilities::setErrors( 'Import Products', 'File is too large' );
					} else {
						self::importProducts( $file['tmp_name'] );
					}
				} else {
					CbdInformationAnalyzerUtilities::setErrors( 'Import Products', 'Invalid file type' );
				}
			} else {
				CbdInformationAnalyzerUtilities::setErrors( 'Import Products', 'Please select a file for upload' );
			}
		}
	}

	protected static function importProducts( string $filePath ) {
		global $wpdb;
		$productTable = $wpdb->prefix . CbdInformationAnalyzerDatabase::PRODUCT_TABLE;
		$spreadsheet  = IOFactory::load( $filePath );
		$worksheet    = $spreadsheet->getActiveSheet()->setAutoFilter(
			$spreadsheet->getActiveSheet()
			            ->calculateWorksheetDimension()
		);
		$highestRow   = $worksheet->getHighestRow();
		for ( $row = 2; $row <= $highestRow; ++ $row ) {
			$productId    = $worksheet->getCell( 'A' . $row )
			                          ->getValue();
			$productName  = $worksheet->getCell( 'B' . $row )
			                          ->getValue();
			$productGroup = $worksheet->getCell( 'C' . $row )
			                          ->getValue();
			$isExist      = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS(SELECT 1 FROM $productTable WHERE `ID` = %d  LIMIT 1)",
				[ $productId ] ) );
			if ( $isExist ) {
				$wpdb->update( $productTable, [
					'name'       => $productName,
					'group_name' => $productGroup,
					'updatedAt'  => ( new DateTime() )->format( 'Y-m-d h:i:s' )
				], [ 'ID' => $productId ] );
			} else {
				$wpdb->insert( $productTable, [
					'ID'         => $productId,
					'name'       => $productName,
					'group_name' => $productGroup,
					'createdAt'  => ( new DateTime() )->format( 'Y-m-d h:i:s' ),
					'updatedAt'  => ( new DateTime() )->format( 'Y-m-d h:i:s' )
				] );
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
		/** @var Product[] $results */
		$results     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $productTable" ) );
		$spreadsheet = new Spreadsheet();
		$spreadsheet
			->getProperties()
			->setCreator( 'CBD Information Analyzer Wordpress Plugin' )
			->setLastModifiedBy( 'CBD Information Analyzer Wordpress Plugin' )
			->setCreated( time() )
			->setModified( $updatedAt )
			->setTitle( 'Example Product import XLSX File' )
			->setSubject( 'Example Product import XLSX File' )
			->setDescription( 'Example Product import XLSX File' )
			->setKeywords( 'example, xlsx, products list' )
			->setCategory( 'Example Files' );

		$sheet = $spreadsheet->setActiveSheetIndex( 0 );
		$sheet->setCellValue( 'A1', 'Product ID' )
		      ->setCellValue( 'B1', 'Name' )
		      ->setCellValue( 'C1', 'Group' );
		$headerStyle = [
			'font'      => [
				'bold' => true,
			],
			'alignment' => [
				'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
			],
		];
		$sheet->getStyle( 'A1:C1' )->applyFromArray( $headerStyle );
		foreach ( $results as $index => $result ) {
			$sheet->setCellValue( 'A' . ( $index + 2 ), $result->ID )
			      ->setCellValue( 'B' . ( $index + 2 ), $result->name )
			      ->setCellValue( 'C' . ( $index + 2 ), $result->group_name );
		}

		$sheet->getColumnDimension( 'A' )->setAutoSize( true );
		$sheet->getColumnDimension( 'B' )->setAutoSize( true );
		$sheet->getColumnDimension( 'C' )->setAutoSize( true );
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="Products List.xlsx"' );
		header( 'Cache-Control: max-age=0' );

		$writer = IOFactory::createWriter( $spreadsheet, 'Xlsx' );
		$writer->save( 'php://output' );
		exit;
	}
}
