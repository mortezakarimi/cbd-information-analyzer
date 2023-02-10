<?php

declare(strict_types = 1);

namespace Mortezakarimi\CbdInformationAnalyzer\Includes;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @see       https://morteza-karimi.ir
 * @since      1.0.0
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 *
 * @author     Morteza Karimi <me@morteza-karimi.ir>
 */
class cbdInformationAnalyzerI18N
{
  /**
   * Load the plugin text domain for translation.
   *
   * @since    1.0.0
   */
  public function load_plugin_textdomain(): void
  {
    load_plugin_textdomain(
      'cbd-information-analyzer',
      false,
      \dirname(plugin_basename(__FILE__), 2) . '/languages/'
    );
  }
}
