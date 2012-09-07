<?php

require_once dirname(__FILE__) . '/../../../../webim/libs/classes/database.php';
require_once dirname(__FILE__) . '/../database_config.php';

/**
 * Test class for Database.
 * Generated by PHPUnit on 2012-07-11 at 12:37:41.
 */
class DatabaseTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Database
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = Database::getInstance();
	}

	public static function setUpBeforeClass() {
		global $db_host, $db_name, $db_user, $db_pass, $tables_prefix,
			$db_encoding, $force_charset_in_connection, $use_persistent_connection;
		Database::initialize(
			$db_host,
			$db_user,
			$db_pass,
			$use_persistent_connection,
			$db_name,
			$tables_prefix,
			$force_charset_in_connection,
			$db_encoding
		);
		$dbh = new PDO(
			"mysql:host={$db_host};dbname={$db_name}",
			$db_user,
			$db_pass
		);
		$dbh->exec(
			"CREATE TABLE phpunit_test_only " .
			"(id INT (10) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))"
		);
		$dbh = NULL;
	}

	public static function tearDownAfterClass() {
		global $db_host, $db_user, $db_pass, $db_name;
		$dbh = new PDO(
			"mysql:host={$db_host};dbname={$db_name}",
			$db_user,
			$db_pass
		);
		$dbh->exec("DROP TABLE phpunit_test_only");
		$dbh = NULL;
		Database::destroy();
	}

	public function testGetInstance() {
		$anotherDatabaseInstance = Database::getInstance();
		$this->assertSame($this->object, $anotherDatabaseInstance);
		$anotherDatabaseInstance = NULL;
	}

	public function testErrorInfo() {
		$this->object->throwExeptions(true);
		$this->assertFalse($this->object->errorInfo());
		try{
			$this->object->query("SOME_FAKE_QUERY");
			$this->fail('Exception must be thrown!');
		} catch(Exception $e) {
			$errorInfo = $this->object->errorInfo();
			$this->assertEquals('42000', $errorInfo[0]);
			$this->assertEquals(1064, $errorInfo[1]);
		}
		$this->object->query("SELECT 'test_value'");
		$errorInfo = $this->object->errorInfo();
		$this->assertEquals('00000', $errorInfo[0]);
	}

	public function testQuery() {
		global $mysqlprefix;

		// Test simple good query
		$this->assertTrue($this->object->query("SELECT 'test_value'"));

		// Test various fetch type
		$result = $this->object->query(
			"SELECT 'test_value_one' AS field_name",
			NULL,
			array('return_rows' => Database::RETURN_ONE_ROW)
		);
		$this->assertEquals('test_value_one', $result['field_name']);

		$result = $this->object->query(
			"SELECT 'test_value_two' AS field_name",
			NULL,
			array(
				'return_rows' => Database::RETURN_ONE_ROW,
				'fetch_type' => Database::FETCH_ASSOC
			)
		);
		$this->assertEquals('test_value_two', $result['field_name']);

		$result = $this->object->query(
			"SELECT 'test_value_four' AS field_name",
			NULL,
			array(
				'return_rows' => Database::RETURN_ONE_ROW,
				'fetch_type' => Database::FETCH_NUM
			)
		);
		$this->assertEquals('test_value_four', $result[0]);

		$result = $this->object->query(
			"SELECT 'test_value_four' AS field_name",
			NULL,
			array(
				'return_rows' => Database::RETURN_ONE_ROW,
				'fetch_type' => Database::FETCH_BOTH
			)
		);
		$this->assertEquals('test_value_four', $result['field_name']);
		$this->assertEquals('test_value_four', $result[0]);

		// Test all rows return
		$result = $this->object->query(
			"SELECT 'test_value_five' AS field_name " .
			"UNION SELECT 'test_value_six' AS field_name",
			NULL,
			array('return_rows' => Database::RETURN_ALL_ROWS)
		);
		$this->assertEquals('test_value_five', $result[0]['field_name']);
		$this->assertEquals('test_value_six', $result[1]['field_name']);

		// Test unnamed placeholders
		$result = $this->object->query(
			"SELECT ? AS field_name ",
			array('test_value_seven'),
			array('return_rows' => Database::RETURN_ONE_ROW)
		);
		$this->assertEquals('test_value_seven', $result['field_name']);

		// Test named placeholders
		$result = $this->object->query(
			"SELECT :name AS field_name ",
			array(':name' => 'test_value_eight'),
			array('return_rows' => Database::RETURN_ONE_ROW)
		);
		$this->assertEquals('test_value_eight', $result['field_name']);

		// Test prefixies
		$result = $this->object->query(
			"SELECT '{test}' AS field_name ",
			NULL,
			array('return_rows' => Database::RETURN_ONE_ROW)
		);
		$this->assertEquals($mysqlprefix.'test', $result['field_name']);
	}

	public function testInsertedId() {
		$this->object->query("INSERT INTO phpunit_test_only (id) VALUES (NULL)");
		$actual_id = $this->object->insertedId();
		list($expected_id) = $this->object->query(
			"SELECT MAX(id) FROM phpunit_test_only",
			NULL,
			array(
				'return_rows' => Database::RETURN_ONE_ROW,
				'fetch_type' => Database::FETCH_NUM
			)
		);
		$this->assertTrue(is_numeric($actual_id));
		$this->assertEquals($expected_id, $actual_id);
	}

	public function testAffectedRows() {
		// Test on INSERT
		$this->object->query(
			"INSERT INTO phpunit_test_only (id) VALUES " .
			"(100), (101), (102), (103), (104), (105)"
		);
		$this->assertEquals(6, $this->object->affectedRows());

		// Test on UPDATE
		$this->object->query(
			"UPDATE phpunit_test_only SET id = id+100 WHERE id > 103"
		);
		$this->assertEquals(2, $this->object->affectedRows());

		// Test on SELECT
		$this->object->query(
			"SELECT * FROM phpunit_test_only WHERE id >= 100 AND id <= 103"
		);
		$this->assertEquals(4, $this->object->affectedRows());

		// Test on DELETE
		$this->object->query(
			"DELETE FROM phpunit_test_only WHERE id >= 100"
		);
		$this->assertEquals(6, $this->object->affectedRows());
	}

}

?>
