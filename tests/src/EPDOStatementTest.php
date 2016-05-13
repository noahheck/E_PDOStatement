<?php
/**
 * Copyright 2015 github.com/noahheck
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class EPDOStatementTest extends PHPUnit_Framework_TestCase
{
	/**
	 * PDO object
	 */
	protected $pdo = false;

	protected function getConfig()
	{
		return include dirname(__FILE__) . "/../config/config.php";
	}

	protected function getPdo()
	{
		if ($this->pdo)
		{
			return $this->pdo;
		}

		$config = $this->getConfig();

		$this->pdo = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password']);

		$this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("EPDOStatement\EPDOStatement", array($this->pdo)));

		return $this->pdo;
	}

	public function testValuesGetInterpolatedIntoQueryStatementWhenBoundIndividuallyAsNamedParameters()
	{
		$pdo = $this->getPdo();

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = :userId AND status = :user_status";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$stmt->bindParam(":userId" 		, $userId 		, PDO::PARAM_INT);
		$stmt->bindParam(":user_status" , $user_status 	, PDO::PARAM_STR);

		$result = $stmt->interpolateQuery();

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/:userId/", $result));
		$this->assertTrue(false == preg_match("/:user_status/", $result));
	}

	public function testValuesGetInterpolatedIntoQueryStatementWhenBoundIndividuallyAsNamedParametersWithoutLeadingColons()
	{
		$pdo = $this->getPdo();

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = :userId AND status = :user_status";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$stmt->bindParam("userId" 		, $userId 		, PDO::PARAM_INT);
		$stmt->bindParam("user_status" , $user_status 	, PDO::PARAM_STR);

		$result = $stmt->interpolateQuery();

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/:userId/", $result));
		$this->assertTrue(false == preg_match("/:user_status/", $result));
	}

	public function testValuesGetInterpolatedIntoQueryStatementWhenBoundIndividuallyAsUnnamedParameters()
	{
		$pdo = $this->getPdo();

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = ? AND status = ?";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$stmt->bindValue(1, $userId 		, PDO::PARAM_INT);
		$stmt->bindValue(2, $user_status 	, PDO::PARAM_STR);

		$result = $stmt->interpolateQuery();

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/\?/", $result));
	}

	public function testValuesGetInterpolatedIntoQueryWhenProvidedAsNamedInputParameters()
	{
		$pdo = $this->getPdo();

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = :userId AND status = :user_status";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$parameters = array(
			  ":userId" 		=> $userId
			, ":user_status" 	=> $user_status
		);

		$result = $stmt->interpolateQuery($parameters);

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/:userId/", $result));
		$this->assertTrue(false == preg_match("/:user_status/", $result));
	}

	public function testValuesGetInterpolatedIntoQueryWhenProvidedAsUnnamedInputParameters()
	{
		$pdo = $this->getPdo();

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = ? AND status = ?";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$parameters = array(
			  $userId
			, $user_status
		);

		$result = $stmt->interpolateQuery($parameters);

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/\?/", $result));
	}

	public function testValuesGetInterpolatedCorrectlyWhenSimilarlyNamedPlaceholdersAreUsed()
	{
		$pdo = $this->getPdo();

		/**
		 * Specific query using similarly named placeholders
		 */
		$query = "UPDATE logs SET logContent = :logContent WHERE log = :log";
		$stmt = $pdo->prepare($query);

		/**
		 * Bind parameters in order to throw off the interpolation
		 */
		$log = 123;
		$logContent = "Test log content";

		$stmt->bindParam(":log" 		, $log 			, PDO::PARAM_INT);
		$stmt->bindParam(":logContent" 	, $logContent 	, PDO::PARAM_STR);

		$result = $stmt->interpolateQuery();

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/log content/", $result));

		$this->assertTrue(false == preg_match("/:logContent/", $result));
		$this->assertTrue(false == preg_match("/:log/", $result));
	}

	public function testInterpolationAllowsSuccessfulExecutionOfQueries()
	{
		$pdo = $this->getPdo();

		$query = "SELECT ? + ? + ?, ?";

		$stmt = $pdo->prepare($query);

		$values = array(1, 1, 1, "test string");

		$stmt->execute($values);

		list($sum, $testString) = $stmt->fetch();

		$this->assertEquals(3, $sum);
		$this->assertEquals("test string", $testString);
	}

	public function tetstInterpolationAllowsSuccessfulExecutionOfQueriesWithNamedPlaceholders()
	{
		$num = 3;
		$string = "someString";

		$pdo = $this->getPdo();

		$query = "SELECT :num, :string";

		$stmt = $pdo->prepare($query);

		$stmt->bindParam(":num", $num, PDO::PARAM_INT);
		$stmt->bindParam(":string", $string, PDO::PARAM_STR);

		$stmt->execute();

		list($sum, $testString) = $stmt->fetch();

		$this->assertEquals(3, $sum);
		$this->assertEquals("test string", $testString);
	}

	public function testValuesAreSuccessfullyInterpolatedIfNoPdoProvidedToEPDOStatement()
	{
		$config = $this->getConfig();

		$pdo = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password']);

		$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("EPDOStatement\EPDOStatement", array()));

		/**
		 * Generic type query with mix of camel-case and _ separated placeholders
		 */
		$query = "SELECT * FROM users WHERE user_id = :userId AND status = :user_status";
		$stmt = $pdo->prepare($query);

		$userId 		= 123;
		$user_status 	= "active";

		$stmt->bindParam(":userId" 		, $userId 		, PDO::PARAM_INT);
		$stmt->bindParam(":user_status" , $user_status 	, PDO::PARAM_STR);

		$result = $stmt->interpolateQuery();

		$this->assertTrue(false != preg_match("/123/", $result));
		$this->assertTrue(false != preg_match("/active/", $result));

		$this->assertTrue(false == preg_match("/:userId/", $result));
		$this->assertTrue(false == preg_match("/:user_status/", $result));
	}

	public function testQueryIsNotChangedIfNoParametersUsedInQuery()
	{
		$pdo = $this->getPdo();

		$query = "SELECT * FROM test_table WHERE id = '123' AND userId = '456'";

		$stmt = $pdo->prepare($query);

		$this->assertEquals($query, $stmt->interpolateQuery());
	}
}
