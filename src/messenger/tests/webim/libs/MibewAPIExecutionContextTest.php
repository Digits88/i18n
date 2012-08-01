<?php

require_once dirname(__FILE__) . '/../../../webim/libs/mibew_api.php';

/**
 * Test class for MibewAPIExecutionContext.
 * Generated by PHPUnit on 2012-07-27 at 15:47:53.
 */
class MibewAPIExecutionContextTest extends PHPUnit_Framework_TestCase {

	public function testStoreFunctionResults() {
		$context = new MibewAPIExecutionContext();
		// Create 'function' array. See Mibew API for details.
		$function = array(
			'function' => 'test_function',
			'arguments' => array(
				'return' => array('microtime' => 'time'),
				'references' => array()
			)
		);

		// Wrong function's results
		$wrong_results = array();

		// Try to catch MibewAPIException with
		// MibewAPIException::VARIABLE_IS_UNDEFINED_IN_RESULT code
		try {
			$context->storeFunctionResults($function, $wrong_results);
			$this->fail("Exception must be thrown");
		} catch(MibewAPIException $e) {
			$this->assertEquals(
				MibewAPIException::VARIABLE_IS_UNDEFINED_IN_RESULT,
				$e->getCode()
			);
		}

		// Correct function's results
		$results = array(
			'microtime' => 'some_microtime_value'
		);

		$context->storeFunctionResults($function, $results);
		return $context;
	}

	/**
	 * @depends testStoreFunctionResults
	 */
	public function testGetResults(MibewAPIExecutionContext $context) {
		$results = $context->getResults();
		$this->assertEquals(
			array('time' => 'some_microtime_value'),
			$results
		);
		//return $context;
	}

	/**
	 * @depends testStoreFunctionResults
	 */
	public function testGetArgumentsList(MibewAPIExecutionContext $context) {
		// Function with wrong references arguments. See Mibew API for details of 'function'
		// array
		$wrong_function = array(
			'function' => 'test',
			'arguments' => array(
				'return' => array(),
				'references' => array(
					// Wrong function number. Execution does not have to many
					// functons results
					'x' => 12
				),
				'x' => 'microtime'
			)
		);

		// Try to catch MibewAPIException with
		// MibewAPIException::WRONG_FUNCTION_NUM_IN_REFERENCE code
		try {
			$context->getArgumentsList($wrong_function);
			$this->fail("Exception must be thrown");
		} catch(MibewAPIException $e) {
			$this->assertEquals(
				MibewAPIException::WRONG_FUNCTION_NUM_IN_REFERENCE,
				$e->getCode()
			);
		}

		// Another wrong function.
		$wrong_function = array(
			'function' => 'test',
			'arguments' => array(
				'return' => array(),
				'references' => array(
					// Wrong argument 'x'. This function does not have this
					// argument
					'x' => 1
				)
			)
		);

		// Try to catch MibewAPIException with
		// MibewAPIException::EMPTY_VARIABLE_IN_REFERENCE code
		try {
			$context->getArgumentsList($wrong_function);
			$this->fail("Exception must be thrown");
		} catch(MibewAPIException $e) {
			$this->assertEquals(
				MibewAPIException::EMPTY_VARIABLE_IN_REFERENCE,
				$e->getCode()
			);
		}

		// Another wrong function.
		$wrong_function = array(
			'function' => 'test',
			'arguments' => array(
				'return' => array(),
				'references' => array(
					'x' => 1
				),
				// Wrong reference name.
				'x' => 'wrong_result'
			)
		);

		// Try to catch MibewAPIException with
		// MibewAPIException::VARIABLE_IS_UNDEFINED_IN_REFERENCE code
		try {
			$context->getArgumentsList($wrong_function);
			$this->fail("Exception must be thrown");
		} catch(MibewAPIException $e) {
			$this->assertEquals(
				MibewAPIException::VARIABLE_IS_UNDEFINED_IN_REFERENCE,
				$e->getCode()
			);
		}

		// Correct function.
		$correct_function = array(
			'function' => 'test',
			'arguments' => array(
				'return' => array(),
				'references' => array(
					'x' => 1
				),
				'x' => 'microtime'
			)
		);

		$arguments = $context->getArgumentsList($correct_function);

		$this->assertEquals(
			array(
				'x' => 'some_microtime_value',
				'return' => array(),
				'references' => array(
					'x' => 1
				)
			),
			$arguments
		);
	}

}

?>
