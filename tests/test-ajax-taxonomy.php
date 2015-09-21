<?php

class Ajaxonomy_Test extends WP_UnitTestCase
{
	/**
	 * @test
	 */
	function class_exists()
	{
		$this->assertTrue( class_exists( 'Megumi\WP\Ajaxonomy' ) );
		$this->assertTrue( class_exists( 'Megumi\WP\Ajaxonomy_Walker' ) );
	}
}

