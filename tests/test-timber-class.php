<?php

class TestTimberClass extends Timber_UnitTestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	function testConstantsDefining() {
		$timber = $GLOBALS['timber'];
		$timber->init_constants();
		$timber->init_constants();
		/* just testing to make sure the double call doesnt error-out */
	}

}
