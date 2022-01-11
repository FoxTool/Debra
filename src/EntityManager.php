<?php

namespace FoxTool\Debra;

/**
* Database connection class
*/
class EntityManager
{
	protected $dbh;
	protected $class;
	protected $tableName;
	private $query;
	public $params;

	public function __construct()
	{
		$database = new Database();
		$this->dbh = $database->connect();
	}

	public function setModel($class)
	{
		if (!empty($class)) {
		    $this->class = $class;
			$this->tableName = $class::getTableName();
		}
		return $this;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function all()
	{
		$dataset = [];

		try {
			if (!empty($this->class)) {
				$this->query = "SELECT * FROM `{$this->tableName}`";
				$stmt = $this->dbh->prepare($this->query);
				$stmt->execute();

				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$obj = new $this->class;
					$props = $obj->getProperties();

					foreach ($props as $property) {
						if (isset($row[$property])) {
							$setter = 'set' . $this->convertFieldName($property);
							$obj->$setter($row[$property]);
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
				if (!empty($this->class)) {
					$this->query = "SELECT * FROM `{$this->tableName}` WHERE `id` = :id LIMIT 0, 1";
					$stmt = $this->dbh->prepare($this->query);
					$stmt->execute(array('id' => $id));

					$row = $stmt->fetch(\PDO::FETCH_ASSOC);
					$obj = new $this->class;
					$props = $obj->getProperties();

					foreach ($props as $property) {
						if (isset($row[$property])) {
							$setter = 'set' . $this->convertFieldName($property);
							$obj->$setter($row[$property]);
						}
					}

					return $obj;

				} else {
					throw new Exception("Empty Model name.", 1);
				}
			} else {
				throw new Exception("ID must be an INTEGER type.", 1);
			}
		} catch (\Exception $e) {
			echo '<strong>Error:</strong> ' . $e->getMessage();
		}
    }

	public function where(Array $conditions)
	{
		try {
			if (empty($conditions) && !is_array($conditions)) {
				throw new Exception("Conditions are empty.", 100);
			}

			if (!empty($this->class)) {
				$this->query = "SELECT * FROM `{$this->tableName}` WHERE ";

				for ($i = 0; $i < count($conditions); $i++) {
					if ($i == count($conditions) - 1) {
						$this->query .= $conditions[$i];
					} else {
						$this->query .= $conditions[$i] . ' AND ';
					}
				}

				return $this;

			} else {
				throw new Exception("Empty Model name.", 1);
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

	public function get()
	{
		$dataSet = [];
		$stmt = $this->dbh->prepare($this->query);
		$stmt->execute($this->params);

		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$obj = new $this->class;
			$props = $obj->getProperties();

			foreach ($props as $property) {
				if (isset($row[$property])) {
					$setter = 'set' . $this->convertFieldName($property);
					$obj->$setter($row[$property]);
				}
			}

			$dataSet[] = $obj;
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
            $values = [];


            if (empty($model->getId())) {
                foreach ($props as $property) {
                    $fields .= "`{$property}`,";
                    $placeholders .= ":{$property},";
                    $getter = 'get' . $this->convertFieldName($property);
                    $values[$property] = $model->$getter($property);
                }

                $fields = rtrim($fields, ',');
                $placeholders = rtrim($placeholders, ',');

                $this->query = "INSERT INTO `{$this->tableName}` ({$fields}) VALUES ({$placeholders});";
            } else {
                foreach ($props as $property) {
                    $fields .= "`{$property}` = :{$property},";
                	$getter = 'get' . $this->convertFieldName($property);
                    $values[$property] = $model->$getter($property);
                }

                $fields = rtrim($fields, ',');
                $this->query = "UPDATE `{$this->tableName}` SET {$fields} WHERE `id` = :id;";
            }
            $stmt = $this->dbh->prepare($this->query);
            $stmt->execute($values);
        } catch (\Exception $e) {
            echo '<strong>Error:</strong> ' . $e->getMessage();
        }
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
}

