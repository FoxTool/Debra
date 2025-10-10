<?php

namespace FoxTool\Debra;

/**
* Entity Manager class
*/
class EntityManager
{
	protected $dbh;
	protected $class;
	protected $tableName;
	private $query;
    private $values = [];
	private $params;
    private $asField;

	public function __construct()
	{
		try {
            $database = new Database();
            $this->dbh = $database->connect();
		} catch(\Exception $e) {
            echo '<strong>Error:</strong> ' . $e->getMessage();
		}
	}

	public function setModel($class)
	{
		if (!empty($class)) {
			$this->class = $class;
			$this->tableName = $class::getTableName();
            $this->params = null;
			$this->query = "SELECT * FROM `{$this->tableName}`";
		}

		return $this;
	}

    /**
     * @param array|string $fields
     */
    public function select($fields)
    {
        if (empty($fields)) {
            throw new \Exception('The fields are not provided!');
        }

        if (gettype($fields) === 'string') {
            if (str_contains($fields, ',')) {
                $fields = explode(',', $fields);
            } else if (str_contains($fields, '*')) {
                $fieldsList = $fields;
            } else {
                throw new \Exception('The fields string is incorrect!');
            }
        }

        if (is_array($fields)) {
            for ($i = 0; $i < count($fields); $i++) {
                $field = '`' . trim($fields[$i]) . '`';
                $fields[$i] = $field;
            }

            $fieldsList = implode(',', $fields);
        }


        $this->query = "SELECT {$fieldsList} FROM `{$this->tableName}`";

        return $this;
    }

    /**
     * @param string $resultField
     */
    public function count($resultField)
    {
        if (empty($resultField)) {
            throw new \Exception('The result field is not provided!');
        }

        $this->asField = $resultField;
        $this->query = "SELECT COUNT(*) as `{$this->asField}` FROM `{$this->tableName}` LIMIT 0 ,1;";

        return $this;
    }

    /**
     * @param string $sourceField
     * @param string $resultField
     */
    public function sum($sourceField, $resultField)
    {
        if (empty($sourceField)) {
            throw new \Exception('The source field is not provided!');
        }

        if (empty($resultField)) {
            throw new \Exception('The result field is not provided!');
        }

        $this->asField = $resultField;
        $this->query = "SELECT SUM({$sourceField}) as `{$this->asField}` FROM `{$this->tableName}` LIMIT 0 ,1;";

        return $this;
    }

    public function calculate()
    {
        if (!empty($this->class) && !is_null($this->class)) {
            $stmt = $this->dbh->prepare($this->query);
            $stmt->execute();

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $obj = new \stdClass;
                $obj->{$this->asField} = $row[$this->asField];
            }

            return $obj;
        }
    }

	public function all()
	{
		$dataset = [];

		try {
			if (!empty($this->class) && !is_null($this->class)) {
				$stmt = $this->dbh->prepare($this->query);
				$stmt->execute();

				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$obj = new $this->class;
					$props = $obj->getProperties();

					foreach ($props as $property) {
                        $field = $this->convertPropertyName($property);
						if (isset($row[$field])) {
							$setter = 'set' . $this->convertFieldName($property);
							$obj->$setter($row[$field]);
						}
					}

					$dataset[] = $obj;
				}
				return $dataset;
			}
		} catch (\Exception $e) {
			echo 'Error: ' . $e->getMessage();
		}
	}

	public function find($id)
	{
		try {
			if (is_numeric($id)) {
				if (!empty($this->class) && !is_null($this->class)) {
					$this->query .= " WHERE `id` = :id LIMIT 0, 1";
					$stmt = $this->dbh->prepare($this->query);
					$stmt->execute(array('id' => $id));

					$row = $stmt->fetch(\PDO::FETCH_ASSOC);
					$obj = new $this->class;
					$props = $obj->getProperties();

					foreach ($props as $property) {
                        $field = $this->convertPropertyName($property);
						if (isset($row[$field])) {
							$setter = 'set' . $this->convertFieldName($property);
							$obj->$setter($row[$field]);
						}
					}

					return $obj;

				} else {
					throw new \Exception("Empty Model name", 1);
				}
			} else {
				throw new \Exception("ID must be an INTEGER type", 1);
			}
		} catch (\Exception $e) {
			echo '<strong>Error:</strong> ' . $e->getMessage();
		}
	}

	public function where(Array $conditions)
	{
		try {
			if (empty($conditions) && !is_array($conditions)) {
				throw new Exception("Conditions are empty", 100);
			}

			if (!empty($this->class) && !is_null($this->class)) {
				$this->query .= " WHERE ";

				for ($i = 0; $i < count($conditions); $i++) {
					if ($i == count($conditions) - 1) {
						$this->query .= $conditions[$i];
					} else {
						$this->query .= $conditions[$i] . ' AND ';
					}
				}

				return $this;

			} else {
				throw new \Exception("Empty Model name", 1);
			}
		} catch (\Exception $e) {
			echo '<strong>Error:</strong> ' . $e->getMessage();
		}
	}

	public function orWhere($conditions)
	{
		$this->query .= ' OR ';
		for ($i = 0; $i < count($conditions); $i++) {
			if ($i == count($conditions) - 1) {
				$this->query .= $conditions[$i];
			} else {
				$this->query .= $conditions[$i] . ' AND ';
			}
		}

		return $this;
	}

	public function setParams($params)
	{
		if (!empty($params) && is_array($params)) {
			$this->params = $params;
		}
		return $this;
	}

	public function limit($limit, $offset = null)
	{
		if (empty($limit) ) {
			throw new \Exception('The "limit" parameter cannot be empty');
		}

		if (!is_numeric($limit)) {
			throw new \Exception('The "limit" parameter should be a number');
		}

		$this->query .= ' LIMIT ' . $limit;

		if (!is_null($offset)) {

			if (!is_numeric($offset)) {
				throw new \Exception('The "offset" parameter should be a number');
			}

			$this->query .= ' OFFSET ' . $offset;
		}

		return $this;
	}

	public function get()
	{
		$dataSet = [];
		$stmt = $this->dbh->prepare($this->query);
		$stmt->execute($this->params);

		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$obj = new $this->class;
			$props = $obj->getProperties();

			foreach ($props as $property) {
				$field = $this->convertPropertyName($property);
				if (isset($row[$field])) {
					$setter = 'set' . $this->convertFieldName($property);
					$obj->$setter($row[$field]);
				}
			}

			$dataSet[] = $obj;
		}
		return $dataSet;
	}

    /**
     * Returns all records from a table as associative array
     * It can use "limit" method before own call
     *
     * @return array
     */
	public function json()
	{
        $dataSet = [];

        $stmt = $this->dbh->prepare($this->query);

        if (is_array($this->params)) {
            $stmt->execute($this->params);
        } else {
            $stmt->execute();
        }

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $dataSet[] = $row;
        }

        return $dataSet;
	}

	public function persist($model)
	{
		if (empty($model)) {
		    return false;
        }

        try {
            $props = $model->getProperties();

            $fields = '';
            $placeholders = '';

            if (empty($model->getId())) {
                foreach ($props as $property) {
                    $field = $this->convertPropertyName($property);
                    $fields .= "`{$field}`, ";
                    $placeholders .= ":{$property},";
                    $getter = 'get' . $this->convertFieldName($property);
                    $this->values[$property] = $model->$getter($property);
                }

                $fields = rtrim($fields, ', ');
                $placeholders = rtrim($placeholders, ',');

                $this->query = "INSERT INTO `{$this->tableName}` ({$fields}) VALUES ({$placeholders});";
            } else {
                foreach ($props as $property) {
                    $field = $this->convertPropertyName($property);
                    $fields .= "`{$field}` = :{$property}, ";
                	$getter = 'get' . $this->convertFieldName($property);
                    $this->values[$property] = $model->$getter($property);
                }

                $fields = rtrim($fields, ', ');
                $this->query = "UPDATE `{$this->tableName}` SET {$fields} WHERE `id` = :id;";
            }

            return $this;
        } catch (\Exception $e) {
            echo '<strong>Error:</strong> ' . $e->getMessage();
        }
	}

    public function save()
    {
        try {
            if (!empty($this->query)) {
                $stmt = $this->dbh->prepare($this->query);
                $stmt->execute($this->values);
            } else {
                throw new \Exception("The query is empty");
            }
        } catch (\Exception $e) {
            echo '<strong>Error:</strong> ' . $e->getMessage();
        }
    }

    public function getQuery()
    {
        return $this->query;
    }

    private function convertFieldName($field)
    {
        $parts = explode('_', $field);
        $parts = array_map(
            function($field) {
                return ucfirst($field);
            },
            $parts
        );
        return implode('', $parts);
    }

    private function convertPropertyName($property)
    {
        $pos = strcspn($property, implode('', range('A', 'Z')));

        if ($pos < strlen($property)) {
            do {
                $first = substr($property, 0,  $pos - strlen($property));
                $last = substr($property, $pos);

                $last = lcfirst($last);
                $property = "{$first}_{$last}";
                $pos = strcspn($property, implode('', range('A', 'Z')));
            } while ($pos < strlen($property));
        }

        return $property;
    }
}
