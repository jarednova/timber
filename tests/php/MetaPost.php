<?php

/**
 * Class MetaPost
 */
class MetaPost extends Timber\Post {
	/**
	 * Public property.
	 *
	 * @var string
	 */
	public $public_property = 'I am a public property';

	/**
	 * Protected property.
	 *
	 * @var string
	 */
	protected $protected_property = 'I am a protected property';

	/**
	 * Public method.
	 *
	 * @return string
	 */
	public function public_method() {
		return 'I am a public method';
	}

	/**
	 * Public method with required arguments.
	 *
	 * @param mixed $arg1 A required argument.
	 *
	 * @return string
	 */
	public function public_method_with_args( $arg1 ) {
		return 'I am a public method';
	}

	/**
	 * Protected method.
	 *
	 * @return string
	 */
	protected function protected_method() {
		return 'I am a protected method';
	}
}
