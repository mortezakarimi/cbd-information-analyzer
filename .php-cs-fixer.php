<?php

$finder = PhpCsFixer\Finder::create()
                           ->exclude( 'vendors' )
                           ->in( __DIR__  );

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.14.3|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();

return $config
	->setRiskyAllowed( true )
	->setIndent( '  ' )
	->setRules( [
		'@PHP74Migration'              => true,
		'@PhpCsFixer:risky'            => true,
		'@PhpCsFixer'                  => true,
		'@PSR2'                        => true,
		'@PSR12:risky'                 => true,
		'@PSR12'                       => true,
		'@PSR1'                        => true,
		'@PHP82Migration'              => true,
		'@PHP81Migration'              => true,
		'@PHP80Migration:risky'        => true,
		'@PHP80Migration'              => true,
		'@PHP74Migration:risky'        => true,
		'@PER'                         => true,
		'@PER:risky'                   => true,
		// Concatenation should be spaced according configuration.
		'concat_space'                 => [ 'spacing' => 'one' ],
		// Equal sign in declare statement should be surrounded by spaces or not following configuration.
		'declare_equal_normalize'      => [ 'space' => 'single' ],
		// There must not be spaces around `declare` statement parentheses.
		'declare_parentheses'          => true,
		// There MUST NOT be a space after the opening parenthesis. There MUST NOT be a space before the closing parenthesis.
		'no_spaces_inside_parenthesis' => false,
		// EXPERIMENTAL: Takes `@param` annotations of non-mixed types and adjusts accordingly the function signature. Requires PHP >= 7.0.
		'phpdoc_to_param_type'         => true,
		// EXPERIMENTAL: Takes `@var` annotation of non-mixed types and adjusts accordingly the property signature. Requires PHP >= 7.4.
		'phpdoc_to_property_type'      => true,
		// EXPERIMENTAL: Takes `@return` annotation of non-mixed types and adjusts accordingly the function signature. Requires PHP >= 7.0.
		'phpdoc_to_return_type'        => true,
	] )
	->setFinder( $finder
	);
