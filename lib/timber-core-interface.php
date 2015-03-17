<?php

interface TimberCoreInterface {

	public function __call( $field, $args );

	public function __get( $field );

	/**
	 * @return boolean
	 */
	public function __isset( $field );

    public function import( $info, $force = false );

    public function get_method_values();
}
