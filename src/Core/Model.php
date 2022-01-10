<?php
namespace Debra\Core;


class Model
{
    public function getProperties()
    {
        $props = [];
        foreach ($this as $prop => $value) {
            $props[] = $prop;
        }

        return $props;
    }

    public static function getTableName()
    {
        if (isset(static::$tableName) && static::$tableName != '') {
            return static::$tableName;
        } else {
            $classFullName = static::class;
            $classParts = explode('\\', $classFullName);
            $className = strtolower($classParts[count($classParts) - 1]);
            $tableName = $className . 's';
            return $tableName;
        }
    }
}
