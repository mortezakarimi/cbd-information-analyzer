<?php

namespace Cbd_Information_Analyzer\Admin\services;

use Cbd_Information_Analyzer\Admin\models\Product;
use Cbd_Information_Analyzer\Admin\models\UserHistory;
use Cbd_Information_Analyzer\Admin\models\UserTarget;
use DateTime;
use Exception;
use NumberFormatter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use WP_User;

/**
 * SkuService
 * @author Morteza Karimi <me@morteza-karimi.ir>
 * @since v1.0
 */
class UserProfileService {
	const DOWNLOAD_XLSX = "Download Excel";
	const DOWNLOAD_PDF = "Download PDF";

	/**
	 * @throws Exception
	 */
	public static function showReportShortcode( $attr ) {
		$attr = shortcode_atts( array(
			'low-style'  => 'background-color: red;color: white;',
			'mid-style'  => 'background-color: #FCC201;color: white;',
			'high-style' => 'background-color: green;color: white;',
			'date'       => ( new DateTime() )->format( DateTime::ATOM )
		), $attr );
		$user = wp_get_current_user();
		if ( null === $user ) {
			return '';
		}

		$percentFormatter = NumberFormatter::create( 'fa_IR', NumberFormatter::PERCENT );
		$numberFormatter  = NumberFormatter::create( 'fa_IR', NumberFormatter::DECIMAL );

		$attr['low-style']  = $attr['low-style'] ?? 'background-color: red;color: white;';
		$attr['mid-style']  = $attr['mid-style'] ?? 'background-color: #FCC201;color: white;';
		$attr['high-style'] = $attr['high-style'] ?? 'background-color: green;color: white;';
		$products           = self::calculatePageInformation( $attr, $user );


		ob_start(); // Start output buffering
		?>
        <table class="cbd-analyzer-profile-table">
            <thead>
            <tr>
                <th colspan="2"><?= __( 'SKU', 'cbd-information-analyzer-textdomain' ) ?></th>
                <th><?= __( 'Target', 'cbd-information-analyzer-textdomain' ) ?>
                    <br><?= __( '(CT)', 'cbd-information-analyzer-textdomain' ) ?></th>
                <th><?= __( 'Sales MTD', 'cbd-information-analyzer-textdomain' ) ?>
                    <br><?= __( '(CT)', 'cbd-information-analyzer-textdomain' ) ?></th>
                <th><?= __( 'Arch%', 'cbd-information-analyzer-textdomain' ) ?></th>
                <th><?= __( 'Trend', 'cbd-information-analyzer-textdomain' ) ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $products as $product ) { ?>
                <tr>
                    <td colspan="2" style="text-align: start;"><?= $product->name ?></td>
                    <td><?= $numberFormatter->format( $product->target ) ?></td>
                    <td><?= $numberFormatter->format( $product->actual ) ?></td>
                    <td><?= $percentFormatter->format( $product->achieved_percent ) ?></td>
                    <td style="<?= $product->trend_style ?>"><?= $percentFormatter->format( $product->trend ) ?></td>
                </tr>
			<?php } ?>
            </tbody>
        </table>
		<?php

		return ob_get_clean(); // Return the buffered content
	}

	/**
	 * @param $attr
	 * @param WP_User $user
	 *
	 * @return object
	 * @throws Exception
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	public static function calculatePageInformation( $attr, WP_User $user ): object {
		$lowStyle  = $attr['low-style'];
		$midStyle  = $attr['mid-style'];
		$highStyle = $attr['high-style'];
		$month     = ( new DateTime( $attr['date'] ) )->format( 'n' );
		$year      = ( new DateTime( $attr['date'] ) )->format( 'Y' );

		$elapsedPercent = self::getElapsedDaysPercent( $month, $year, $user );
		$products       = Product::all();
		if ( ! empty( $_GET['category'] ) ) {
			$products = $products->where( 'group_name', sanitize_text_field( $_GET['category'] ) );
		}

		return $products
			->map( function ( Product $product ) use ( $user, $month, $year, $elapsedPercent, $lowStyle, $midStyle, $highStyle ) {
				$result               = UserHistoryService::calculateTargetActualByProduct(
					$user->ID,
					$product->ID,
					$month,
					$year
				);
				$product['target']    = $result['target'] ?? 0;
				$product['actual']    = $result['actual'] ?? 0;
				$product['remaining'] = $product['target'] > $product['actual'] ? $product['target'] - $product['actual'] : 0;

				$product['remaining_percent'] = 0;
				if ( $product['target'] > 0 ) {
					$product['remaining_percent'] = ( $product['remaining'] / $product['target'] );
				}
				$product['achieved_percent'] = 0;
				if ( $product['target'] > 0 ) {
					$product['achieved_percent'] = ( $result['actual'] / $product['target'] );
				}
				$product['trend'] = is_numeric( $product['achieved_percent'] ) && $elapsedPercent > 0 ? ( $product['achieved_percent'] / $elapsedPercent ) : 0;

				$product['trend_style'] = $lowStyle;
				if ( $product['trend'] > .75 && $product['trend'] <= .99 ) {
					$product['trend_style'] = $midStyle;
				} elseif ( $product['trend'] >= 1 ) {
					$product['trend_style'] = $highStyle;
				}

				return $product;
			} )->sortBy( 'ID', SORT_REGULAR, 'ASC' );
	}

	/**
	 * @param string $month
	 * @param string $year
	 * @param WP_User $user
	 *
	 * @return float|int
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function getElapsedDaysPercent( string $month, string $year, WP_User $user ) {
		$getMonthTotalWorkingDays = self::getTotalWorkingDays( $month, $year, $user );
		$getTotalElapsedDays      = self::getTotalElapsedDays( $month, $year );

		return $getMonthTotalWorkingDays === 0 ? 0 : $getTotalElapsedDays / $getMonthTotalWorkingDays;
	}

	/**
	 * @param string $month
	 * @param string $year
	 * @param WP_User $user
	 *
	 * @return int
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function getTotalWorkingDays( string $month, string $year, WP_User $user ): int {
		$getMonthTotalWorkingDays = (int) UserTarget::query()
		                                            ->where( 'target_month', $month )
		                                            ->where( 'target_year', $year )
		                                            ->where( 'USER_ID', $user->ID )
		                                            ->groupBy( 'total_working_days' )
		                                            ->value( 'total_working_days' );

		return $getMonthTotalWorkingDays;
	}

	/**
	 * @param string $month
	 * @param string $year
	 *
	 * @return int
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function getTotalElapsedDays( string $month, string $year ): int {
		return (int) UserHistory::query()
		                        ->selectRaw( 'COUNT(DISTINCT DATE(changeAt)) as total_days' )
		                        ->whereMonth( 'changeAt', '=', $month )
		                        ->whereYear( 'changeAt', '=', $year )
		                        ->value( 'total_days' );
	}

	public static function generateXlsx( WP_User $user, string $month, string $year, $products ) {
		$spreadsheet = self::generateBaseSheet( $user, $month, $year, $products );
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( "Content-Disposition: attachment;filename=\"Report for $user->first_name $user->last_name - $month-$year\".xlsx" );
		header( 'Cache-Control: max-age=0' );
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );

		$writer = IOFactory::createWriter( $spreadsheet, IOFactory::WRITER_XLS );
		$writer->save( 'php://output' );
		exit;
	}

	/**
	 * @param WP_User $user
	 * @param string $month
	 * @param string $year
	 * @param $products
	 *
	 * @return Spreadsheet
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @author Morteza Karimi <me@morteza-karimi.ir>
	 * @since v1.0
	 */
	private static function generateBaseSheet( WP_User $user, string $month, string $year, $products ): Spreadsheet {
		$spreadsheet = new Spreadsheet();
		$spreadsheet
			->getProperties()
			->setCreator( 'CBD Information Analyzer Wordpress Plugin' )
			->setLastModifiedBy( 'CBD Information Analyzer Wordpress Plugin' )
			->setCreated( time() )
			->setModified( time() )
			->setTitle( "Report for $user->first_name $user->last_name - $month-$year" )
			->setSubject( "Report for $user->first_name $user->last_name - $month-$year" )
			->setDescription( "Report for $user->first_name $user->last_name - $month-$year" )
			->setKeywords( "Report for $user->first_name $user->last_name - $month-$year" )
			->setCategory( 'Report' );

		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setRightToLeft( true );
		$sheet
			->setTitle( $user->user_login )
			->setCellValue( 'A1', __( 'SKU', 'cbd-information-analyzer-textdomain' ) )
			->setCellValue( 'B1', __( 'Target', 'cbd-information-analyzer-textdomain' ) )
			->setCellValue( 'C1', __( 'Sales MTD', 'cbd-information-analyzer-textdomain' ) )
			->setCellValue( 'D1', __( 'Arch%', 'cbd-information-analyzer-textdomain' ) )
			->setCellValue( 'E1', __( 'Trend', 'cbd-information-analyzer-textdomain' ) );

		$headerStyle = [
			'font'      => [
				'bold' => true,
			],
			'alignment' => [
				'horizontal' => Alignment::HORIZONTAL_CENTER,
			],
		];
		$sheet->getStyle( 'A1:E1' )->applyFromArray( $headerStyle );

		$rowNumber = 2;
		foreach ( $products as $product ) {

			$sheet->setCellValue( 'A' . $rowNumber, $product->name )
			      ->setCellValue( 'B' . $rowNumber, $product->target )
			      ->setCellValue( 'C' . $rowNumber, $product->actual )
			      ->setCellValue( 'D' . $rowNumber, $product->achieved_percent )
			      ->setCellValue( 'E' . $rowNumber, $product->trend );

			$sheet->getStyle( 'B' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
			$sheet->getStyle( 'C' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_NUMBER );
			$sheet->getStyle( 'D' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_PERCENTAGE_00 );
			$sheet->getStyle( 'E' . $rowNumber )->getNumberFormat()->setFormatCode( NumberFormat::FORMAT_PERCENTAGE_00 );
			$styles = self::convertStyleToArray( $product->trend_style );
			$sheet->getStyle( 'E' . $rowNumber )->getFill()
			      ->setFillType( \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID )
			      ->getStartColor()->setARGB( str_replace( '#', '', $styles['background-color'] ) ?? 'FFFFFF' );
			$sheet->getStyle( 'E' . $rowNumber )->getFont()
			      ->getColor()
			      ->setARGB( $styles['color'] ?? '000000' );
			++ $rowNumber;
		}

		$sheet->getColumnDimension( 'A' )->setAutoSize( true );
		$sheet->getColumnDimension( 'B' )->setAutoSize( true );
		$sheet->getColumnDimension( 'C' )->setAutoSize( true );
		$sheet->getColumnDimension( 'D' )->setAutoSize( true );
		$sheet->getColumnDimension( 'E' )->setAutoSize( true );
		$sheet->freezePane( 'F2' );

		return $spreadsheet;
	}

	private static function convertStyleToArray( $style ) {
		$r = array();
		preg_match_all( "/([\w-]+)\s*:\s*([^;]+)\s*;?/", $style, $arr, PREG_SET_ORDER );
		foreach ( $arr as $v ) {
			$r[ $v[1] ] = $v[2];
		}

		return $r;
	}

	public static function generatePDF( WP_User $user, string $month, string $year, $products ) {
		$spreadsheet = self::generateBaseSheet( $user, $month, $year, $products );
		header( 'Content-Type: application/pdf' );
		header( "Content-Disposition: attachment;filename=\"Report for $user->first_name $user->last_name - $month-$year\".pdf" );
		header( 'Cache-Control: max-age=0' );
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );
		\PhpOffice\PhpSpreadsheet\IOFactory::registerWriter( 'Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class );
		$writer = IOFactory::createWriter( $spreadsheet, 'Pdf' );
		$writer->setOrientation( \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE );
		$writer->save( 'php://output' );
		exit;
	}

	public static function showWorkingDaysShortcode( $attr ) {
		$attr = shortcode_atts( array(
			'date' => ( new DateTime() )->format( DateTime::ATOM )
		), $attr );
		$user = wp_get_current_user();
		if ( null === $user ) {
			return '';
		}
		$month                    = ( new DateTime( $attr['date'] ) )->format( 'n' );
		$year                     = ( new DateTime( $attr['date'] ) )->format( 'Y' );
		$numberFormatter          = NumberFormatter::create( 'fa_IR', NumberFormatter::DECIMAL );
		$getMonthTotalWorkingDays = self::getTotalWorkingDays( $month, $year, $user );
		ob_start(); // Start output buffering
		?>
        <div>
            <div><?= __( 'Total Working Days', 'cbd-information-analyzer-textdomain' ) ?></div>
            <div><?= $numberFormatter->format( $getMonthTotalWorkingDays ) ?></div>
        </div>
		<?php
		return ob_get_clean();
	}

	public static function showElapsedWorkingDaysShortcode( $attr ) {
		$attr                = shortcode_atts( array(
			'date' => ( new DateTime() )->format( DateTime::ATOM )
		), $attr );
		$month               = ( new DateTime( $attr['date'] ) )->format( 'n' );
		$year                = ( new DateTime( $attr['date'] ) )->format( 'Y' );
		$numberFormatter     = NumberFormatter::create( 'fa_IR', NumberFormatter::DECIMAL );
		$getTotalElapsedDays = self::getTotalElapsedDays( $month, $year );
		ob_start(); // Start output buffering
		?>
        <div>
            <div><?= __( 'Total Elapsed Days', 'cbd-information-analyzer-textdomain' ) ?></div>
            <div><?= $numberFormatter->format( $getTotalElapsedDays ) ?></div>
        </div>
		<?php
		return ob_get_clean();
	}

	public static function showElapsedDaysPercentShortcode( $attr ) {
		$attr = shortcode_atts( array(
			'date' => ( new DateTime() )->format( DateTime::ATOM )
		), $attr );
		$user = wp_get_current_user();
		if ( null === $user ) {
			return '';
		}
		$month               = ( new DateTime( $attr['date'] ) )->format( 'n' );
		$year                = ( new DateTime( $attr['date'] ) )->format( 'Y' );
		$percentFormatter    = NumberFormatter::create( 'fa_IR', NumberFormatter::PERCENT );
		$getTotalElapsedDays = self::getElapsedDaysPercent( $month, $year, $user );
		ob_start(); // Start output buffering
		?>
        <div>
            <div><?= __( 'Total Elapsed Days', 'cbd-information-analyzer-textdomain' ) ?></div>
            <div><?= $percentFormatter->format( $getTotalElapsedDays ) ?></div>
        </div>
		<?php
		return ob_get_clean();
	}

	public static function showFilterShortcode( $attr ) {
		$categories = Product::query()->groupBy( 'group_name' )->pluck( 'group_name' );
		ob_start(); // Start output buffering
		?>
        <form method="get">
            <label>
                <select name="category">
                    <option value=""
						<?= empty( $_GET['category'] ) ? 'selected' : '' ?>><?= __( 'All Categories', 'cbd-information-analyzer-textdomain' ) ?></option>
					<?php foreach ( $categories as $category ): ?>
                        <option value="<?= esc_attr__( $category, 'cbd-information-analyzer-textdomain' ) ?>"
							<?= esc_attr__( $category, 'cbd-information-analyzer-textdomain' ) == sanitize_text_field( $_GET['category'] ?? '' ) ? 'selected' : '' ?>><?= esc_attr__( $category, 'cbd-information-analyzer-textdomain' ) ?></option>
					<?php endforeach; ?>
                </select>
            </label>
            <button type="submit"><?= __( 'Filter', 'cbd-information-analyzer-textdomain' ) ?></button>
        </form>
		<?php
		return ob_get_clean();
	}

	public static function showDownloadButtonsShortcode( $attr ) {
		$attr = shortcode_atts( array(
			'low-style'  => 'background-color: red;color: white;',
			'mid-style'  => 'background-color: #FCC201;color: white;',
			'high-style' => 'background-color: green;color: white;',
			'date'       => ( new DateTime() )->format( DateTime::ATOM )
		), $attr );
		$user = wp_get_current_user();
		if ( null === $user ) {
			return '';
		}
		// Generate the link with a unique identifier
		$uniqueId  = uniqid( '', false );
		$reportUrl = add_query_arg( 'generate_user_report', $uniqueId, home_url() );
		if ( ! empty( $_GET['category'] ) ) {
			$reportUrl = add_query_arg( 'category', sanitize_text_field( $_GET['category'] ), $reportUrl );
		}

		ob_start(); // Start output buffering
		?>
        <form method="POST" action="<?= esc_url( $reportUrl ) ?>">
            <input name="form_nonce" type="hidden" value="<?= wp_create_nonce( 'report-user-nonce' ) ?>"/>
            <input name="low-style" type="hidden" value="<?= $attr['low-style'] ?>"/>
            <input name="mid-style" type="hidden" value="<?= $attr['mid-style'] ?>"/>
            <input name="high-style" type="hidden" value="<?= $attr['high-style'] ?>"/>
            <input name="date" type="hidden" value="<?= $attr['date'] ?>"/>
            <input type="submit" name="generate_file" value="<?= self::DOWNLOAD_XLSX ?>">
            <input type="submit" name="generate_file" value="<?= self::DOWNLOAD_PDF ?>">
        </form>
		<?php
		return ob_get_clean();
	}
}
