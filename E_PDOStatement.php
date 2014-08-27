<?php
/**
 * Copyright 2014 github.com/noahheck
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

class E_PDOStatement extends \PDOStatement
{
	/**
	 * The first argument passed in should be an instance of the PDO object. If so, we'll cache it's reference locally
	 * to allow for the best escaping possible later when interpolating our query. Other parameters can be added if
	 * needed.
	 * @param \PDO $pdo
	 */
	protected function __construct($pdo)
	{
		if ($pdo instanceof PDO)
		{
			$this->_pdo = $pdo;
		}
	}

	/**
	 * @var \PDO $_pdo
	 */
	protected $_pdo = "";

	/**
	 * @var str $fullQuery - will be populated with the interpolated db query string
	 */
	public $fullQuery;

	/**
	 * @var array $boundParams - array of arrays containing values that have been bound to the query as parameters
	 */
	protected $boundParams = array();

	/**
	 * Overrides the default \PDOStatement method to add the named parameter and it's reference to the array of bound
	 * parameters - then accesses and returns parent::bindParam method
	 * @param str $param
	 * @param var $value
	 * @param int $datatype
	 * @param int $length
	 * @param mixed $driverOptions
	 * @return bool - default of \PDOStatement::bindParam()
	 */
	public function bindParam($param, &$value, $datatype = PDO::PARAM_STR, $length = 0, $driverOptions = false)
	{
		$this->boundParams[$param] = &$value;

		return parent::bindParam($param, $value, $datatype, $length, $driverOptions);
	}

	/**
	 * Overrides the default \PDOStatement method to add the named parameter and it's value to the array of bound values
	 * - then accesses and returns parent::bindValue method
	 * @param str $param
	 * @param str $value
	 * @param int $datatype
	 * @return bool - default of \PDOStatement::bindValue()
	 */
	public function bindValue($param, $value, $datatype = PDO::PARAM_STR)
	{
		$this->boundParams[$param] = $value;

		return parent::bindValue($param, $value, $datatype);
	}

	/**
	 * Copies $this->queryString then replaces bound markers with associated values ($this->queryString is not modified
	 * but the resulting query string is assigned to $this->fullQuery)
	 * @param array $inputParams - array of values to replace ? marked parameters in the query string
	 * @return str $testQuery - interpolated db query string
	 */
	public function interpolateQuery($inputParams = null)
	{
		$testQuery = $this->queryString;

		/**
		 * If parameters were bound prior to execution, boundParams will be true
		 */
		if ($this->boundParams)
		{
			//	We ksort our bound parameters array to allow parameter binding to numbered ? markers and we need to
			//	replace them in the correct order
			ksort($this->boundParams);

			foreach ($this->boundParams as $key => $array)
			{
				$key 		= (is_numeric($key)) ? "\?" : $key;
				$value 		= $array;

				$testParam 	= "/" . $key . "/";
				$replValue 	= $this->_prepareValue($value);

				$testQuery 	= preg_replace($testParam, $replValue, $testQuery, 1);
			}
		}

		/**
		 * Otherwise, if we have input parameters, we'll replace ? markers
		 * UPDATE - we can now accept $key => $value named parameters as well:
		 * $inputParams = array(
		 *   ":username" => $username
		 * , ":password" => $password
		 * );
		 */
		if (is_array($inputParams) && $inputParams !== array())
		{
			ksort($inputParams);
			foreach ($inputParams as $key => $replValue)
			{
				$key 		= (is_numeric($key)) ? "\?" : $key;

				$testParam 	= "/" . $key . "/";
				$replValue 	= $this->_prepareValue($replValue);

				$testQuery 	= preg_replace($testParam, $replValue, $testQuery, 1);
			}
		}

		$this->fullQuery = $testQuery;

		return $testQuery;
	}

	/**
	 * Overrides the default \PDOStatement method to generate the full query string - then accesses and returns 
	 * parent::execute method
	 * @param array $inputParams
	 * @return bool - default of \PDOStatement::execute()
	 */
	public function execute($inputParams = null)
	{
		$this->interpolateQuery($inputParams);

		return parent::execute($inputParams);
	}

	/**
	 * Prepares values for insertion into the resultant query string - if $this->_pdo is a valid PDO object, we'll use
	 * that PDO driver's quote method to prepare the query value. Otherwise:
	 * 
	 *  	addslashes is not suitable for production logging, etc. You can update this method to perform the necessary
	 * 		escaping translations for your database driver. Please consider updating your processes to provide a valid
	 * 		PDO object that can perform the necessary translations and can be updated with your i.e. package management,
	 * 		PEAR updates, etc.
	 * 
	 * @param str $value - the value to be prepared for injection as a value in the query string
	 * @return str $value - prepared $value
	 */
	private function _prepareValue($value)
	{
		if ($this->_pdo && ($this->_pdo instanceof PDO))
		{
			$value = $this->_pdo->quote($value);
		}
		else
		{
			$value = "'" . addslashes($value) . "'";
		}

		return $value;
	}
}

?>
