<?php

namespace Timber;

/**
 * This is leftovers after Timber's extensions to Twig was moved into an independent Twig extension class, TwigExtension.
 * 
 * @codeCoverageIgnore
 */
class Twig {

	/**
	 * This property was not used internally prior moving class methods to TwigExtension...
	 * 
	 * @codeCoverageIgnore
	 */
	public static $dir_name;

	/**
	 * @codeCoverageIgnore
	 */
	public static function init() {
		// This is preserved due to the existence of then $dir_name property
		new self();
	}
}
