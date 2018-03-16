<?php
/**
 * Copyright 2017 github.com/noahheck
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

namespace EPDOStatement;

use \PDO as PDO;
use \PDOStatement as PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class EPDOStatement extends PDOStatement implements LoggerAwareInterface
{
    const WARNING_USING_ADDSLASHES = "addslashes is not suitable for production logging, etc. Please consider updating your processes to provide a valid PDO object that can perform the necessary translations and can be updated with your e.g. package management, etc.";

    /**
     * @var \PDO $_pdo
     */
    protected $_pdo = "";

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string $fullQuery - will be populated with the interpolated db query string
     */
    public $fullQuery;

    /**
     * @var array $boundParams - array of arrays containing values that have been bound to the query as parameters
     */
    protected $boundParams = array();

    /**
     * The first argument passed in should be an instance of the PDO object. If so, we'll cache it's reference locally
     * to allow for the best escaping possible later when interpolating our query. Other parameters can be added if
     * needed.
     * @param \PDO $pdo
     */
    protected function __construct(PDO $pdo = null)
    {
        if ($pdo) {
            $this->_pdo = $pdo;
        }
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Overrides the default \PDOStatement method to add the named parameter and it's reference to the array of bound
     * parameters - then accesses and returns parent::bindParam method
     * @param string $param
     * @param mixed $value
     * @param int $datatype
     * @param int $length
     * @param mixed $driverOptions
     * @return bool - default of \PDOStatement::bindParam()
     */
    public function bindParam($param, &$value, $datatype = PDO::PARAM_STR, $length = 0, $driverOptions = false)
    {
        if ($this->logger) {
            $this->logger->debug(
                "Binding parameter {param} (as parameter) as datatype {datatype}: current value {value}",
                array(
                    "param"    => $param,
                    "datatype" => $datatype,
                    "value"    => $value,
                ));
        }

        $this->boundParams[$param] = array(
              "value"       => &$value
            , "datatype"    => $datatype
        );

        return parent::bindParam($param, $value, $datatype, $length, $driverOptions);
    }

    /**
     * Overrides the default \PDOStatement method to add the named parameter and it's value to the array of bound values
     * - then accesses and returns parent::bindValue method
     * @param string $param
     * @param mixed $value
     * @param int $datatype
     * @return bool - default of \PDOStatement::bindValue()
     */
    public function bindValue($param, $value, $datatype = PDO::PARAM_STR)
    {
        if ($this->logger) {
            $this->logger->debug(
                "Binding parameter {param} (as value) as datatype {datatype}: value {value}",
                array(
                    "param"    => $param,
                    "datatype" => $datatype,
                    "value"    => $value,
                ));
        }

        $this->boundParams[$param] = array(
              "value"       => $value
            , "datatype"    => $datatype
        );

        return parent::bindValue($param, $value, $datatype);
    }

    /**
     * Copies $this->queryString then replaces bound markers with associated values ($this->queryString is not modified
     * but the resulting query string is assigned to $this->fullQuery)
     * @param array $inputParams - array of values to replace ? marked parameters in the query string
     * @return string $testQuery - interpolated db query string
     */
    public function interpolateQuery($inputParams = null)
    {
        if ($this->logger) {
            $this->logger->debug("Interpolating query...");
        }

        $testQuery = $this->queryString;

        $params = ($this->boundParams) ? $this->boundParams : $inputParams;

        if ($params) {

            ksort($params);

            foreach ($params as $key => $value) {

                $replValue = (is_array($value)) ? $value
                                                : array(
                                                      'value'       => $value
                                                    , 'datatype'    => PDO::PARAM_STR
                                                );

                $replValue = $this->prepareValue($replValue);

                $testQuery = $this->replaceMarker($testQuery, $key, $replValue);

            }
        }

        $this->fullQuery = $testQuery;

        if ($this->logger) {
            $this->logger->debug("Query interpolation complete");
            $this->logger->debug("Interpolated query: {query}", array("query" => $testQuery));
        }

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

        try {
            $response = parent::execute($inputParams);

            if ($this->logger) {
                $this->logger->info("Query executed: {query}", array("query" => $this->fullQuery));
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception thrown executing query: {query}", array("query" => $this->fullQuery));
                $this->logger->error($e->getMessage(), array("exception" => $e));
            }

            throw $e;
        }

        return $response;
//        return parent::execute($inputParams);
    }

    private function replaceMarker($queryString, $marker, $replValue)
    {
        /**
         * UPDATE - Issue #3
         * It is acceptable for bound parameters to be provided without the leading :, so if we are not matching
         * a ?, we want to check for the presence of the leading : and add it if it is not there.
         */
        if (is_numeric($marker)) {
            $marker = "\?";
        } else {
            $marker = (preg_match("/^:/", $marker)) ? $marker : ":" . $marker;
        }

        $testParam = "/({$marker}(?!\w))(?=(?:[^\"']|[\"'][^\"']*[\"'])*$)/";

        if ($this->logger) {
            $this->logger->debug(
                "Replacing marker {marker} with value {value}",
                array(
                    "marker" => $marker,
                    "value"  => $replValue,
                ));
        }

        return preg_replace($testParam, $replValue, $queryString, 1);
    }

    /**
     * Prepares values for insertion into the resultant query string - if $this->_pdo is a valid PDO object, we'll use
     * that PDO driver's quote method to prepare the query value. Otherwise:
     *
     *      addslashes is not suitable for production logging, etc. You can update this method to perform the necessary
     *      escaping translations for your database driver. Please consider updating your processes to provide a valid
     *      PDO object that can perform the necessary translations and can be updated with your e.g. package management,
     *      etc.
     *
     * @param array $value - an array representing the value to be prepared for injection as a value in the query string
     *                       with it's associated datatype:
     *                       ['datatype' => PDO::PARAM_STR, 'value' => 'something']
     * @return string $value - prepared $value
     */
    private function prepareValue($value)
    {
        if ($value['value'] === NULL) {
            if ($this->logger) {
                $this->logger->debug("Value is null: returning 'NULL'");
            }

            return 'NULL';
        }

        if (!$this->_pdo) {
            if ($this->logger) {
                $this->logger->debug("Preparing value {value} using addslashes", array("value" => $value));
                $this->logger->warning(self::WARNING_USING_ADDSLASHES);
            }

            return "'" . addslashes($value['value']) . "'";
        }

        if (PDO::PARAM_INT === $value['datatype']) {
            if ($this->logger) {
                $this->logger->debug("Preparing value {value} as integer", array("value" => $value));
            }

            return (int) $value['value'];
        }

        if ($this->logger) {
            $this->logger->debug("Preparing value {value} as string", array("value" => $value));
        }

        return  $this->_pdo->quote($value['value']);
    }
}
