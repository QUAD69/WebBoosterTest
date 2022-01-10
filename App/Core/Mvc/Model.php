<?php
declare(strict_types=1);

namespace App\Core\Mvc;

use App\Core\Application;
use App\Core\DI;

/**
 * @method mixed beforeSave(static|object $previous)
 * @method mixed beforeInsert(static|object $previous)
 * @method mixed beforeUpdate(static|object $previous)
 * @method mixed beforeDelete(static|object $previous)
 * @method void afterSave(static|object $previous)
 * @method void afterInsert(static|object $previous)
 * @method void afterUpdate(static|object $previous)
 * @method void afterDelete(static|object $previous)
 */
abstract class Model extends DI
{
    public const TABLE_NAME = '';

    public const COLUMN_PRIMARY = '';

    public const COLUMN_INSERT_TIME = '';

    public const COLUMN_UPDATE_TIME = '';

    public const COLUMN_DELETE_TIME = '';

    protected static array $instances = [];

    protected array $previous;

    protected array $variables;

    public function __construct()
    {
        $this->previous = $this->getCurrentValues();
    }

    public function getCurrentValues(): array
    {
        $values = get_object_vars($this);

        unset($values['previous'], $values['variables']);

        return $values;
    }

    public function getChangedValues(): array
    {
        return array_diff_assoc($this->getCurrentValues(), $this->previous);
    }

    public function save(): bool
    {
        if ($primaryColumn = static::COLUMN_PRIMARY and $tableName = static::TABLE_NAME) {
            $previousValuesObj = (object) $this->previous;

            if (!method_exists($this, 'beforeSave') or $this->beforeSave($previousValuesObj) !== false) {
                $savingTime = time();

                if ($updateTimeColumn = static::COLUMN_UPDATE_TIME) {
                    $this->{$updateTimeColumn} = $savingTime;
                }

                if (empty($this->{$primaryColumn})) {

                    if (!method_exists($this, 'beforeInsert') or $this->beforeInsert($previousValuesObj) !== false) {

                        if ($insertColumn = static::COLUMN_INSERT_TIME) {
                            $this->{$insertColumn} = $savingTime;
                        }

                        if ($currentValues = $this->getCurrentValues()) {
                            $sqlColumn = array();
                            $sqlValues = array();

                            foreach ($currentValues as $column => $value) {
                                $sqlColumn[] = "`{$column}`";
                                $sqlValues[] = self::escape($value);
                            }

                            $sqlColumn = implode(', ', $sqlColumn);
                            $sqlValues = implode(', ', $sqlValues);

                            $sqlQuery = "INSERT INTO `{$tableName}` ({$sqlColumn}) VALUES ({$sqlValues})";

                            if ($query = $this->db->query($sqlQuery) and $query->rowCount() === 1) {
                                if ($lastInsertId = $this->db->lastInsertId()) {
                                    $this->{$primaryColumn} = intval($lastInsertId);
                                }

                                $this->previous = $currentValues;

                                if (method_exists($this,'afterInsert')) {
                                    $this->afterInsert($previousValuesObj);
                                }

                                if (method_exists($this, 'afterSave')) {
                                    $this->afterSave($previousValuesObj);
                                }

                                return true;
                            }
                        }
                    }

                } else {

                    if (!method_exists($this, 'beforeUpdate') or $this->beforeUpdate($previousValuesObj)) {

                        if ($changedValues = $this->getChangedValues()) {
                            $sqlArray = array();

                            foreach ($changedValues as $column => $value) {
                                $sqlArray[] = sprintf("`%s` = %s", $column, self::escape($value));
                            }

                            $primary = self::escape($this->{$primaryColumn});
                            $sqlArray = implode(', ', $sqlArray);
                            $sqlQuery = "UPDATE `{$tableName}` SET {$sqlArray} WHERE `{$primaryColumn}` = {$primary} LIMIT 1";

                            if ($query = $this->db->query($sqlQuery) and $query->rowCount() === 1) {
                                $this->previous = array_merge($this->previous, $changedValues);

                                if (method_exists($this, 'afterUpdate')) {
                                    $this->afterUpdate($previousValuesObj);
                                }

                                if (method_exists($this, 'afterSave')) {
                                    $this->afterSave($previousValuesObj);
                                }

                                return true;
                            }
                        }
                    }

                }
            }
        }

        return false;
    }

    public function delete(): bool
    {
        if ($primaryColumn = static::COLUMN_PRIMARY and $tableName = static::TABLE_NAME) {
            $previousValuesObj = (object)$this->previous;

            if (!method_exists($this, 'beforeDelete') or $this->beforeDelete($previousValuesObj)) {
                $database = Application::$instance->db;
                $primaryValue = self::escape($this->{$primaryColumn});

                if ($deleteColumn = static::COLUMN_DELETE_TIME) {
                    $deleteTime = time();
                    $sqlQuery = "UPDATE `{$tableName}` SET `{$deleteColumn}` = {$deleteTime} WHERE `{$primaryColumn}` = {$primaryValue} LIMIT 1";
                } else {
                    $sqlQuery = "DELETE FROM `{$tableName}` WHERE `{$primaryColumn}` = {$primaryValue} LIMIT 1";
                }

                if ($query = $database->query($sqlQuery) and $query->rowCount() === 1) {
                    unset(static::$instances[static::class][$this->{$primaryColumn}]);

                    if (method_exists($this, 'afterDelete')) {
                        $this->afterDelete($previousValuesObj);
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $where Условие выборки
     * @param array $binds Подстановка аргументов
     * @param array $order Сортировка выборки
     * @param int $limit Лимит выборки
     * @param int $offset Смещение выборки
     * @return static[] Массив объектов данной модели
     */
    public static function find(string $where = '', array $binds = [], array $order = [], int $limit = 0, int $offset = 0): array
    {
        if ($query = static::findQuery([], $where, $binds, $order, $limit, $offset) and $query->rowCount() > 0) {
            return $query->fetchAll(\PDO::FETCH_CLASS, static::class);
        }

        return [];
    }

    public static function findOne(string $where = '', array $binds = [], array $order = [], int $offset = 0): ?static
    {
        if ($query = static::findQuery([], $where, $binds, $order, 1, $offset) and $query->rowCount() === 1) {
            return $query->fetchObject(static::class);
        }

        return null;
    }

    public static function findByPrimary(mixed $identifier): ?static
    {
        $class = static::class;
        $column = static::COLUMN_PRIMARY;
        $escaped = self::escape($identifier);

        static::$instances[$class] ??= [];

        static::$instances[$class][$identifier] ??= static::findOne("`{$column}` = {$escaped}");

        return static::$instances[$class][$identifier];
    }

    public static function number(string $where = '', array $binds = []): int
    {
        if ($query = static::findQuery('COUNT(1)', $where, $binds) and $query->rowCount() === 1) {
            return intval($query->fetch(\PDO::FETCH_NUM)[0]);
        }

        return 0;
    }

    public static function exists(string $where = '', array $binds = []): bool
    {
        return ($query = static::findQuery('TRUE', $where, $binds, limit: 1) and $query->rowCount() === 1);
    }

    public function reset(): void
    {
        foreach ($this->previous as $column => $value) {
            $this->{$column} = $value;
        }
    }

    protected static function findQuery(array|string $columns = '', string $where = '', array $binds = [], array $order = [], int $limit = 0, int $offset = 0, bool $deleted = false): ?\PDOStatement
    {
        $sqlQuery = 'SELECT';

        if (empty($columns)) {
            $sqlQuery .= ' *';
        } elseif (is_array($columns)) {
            $sqlQuery .= ' `' . implode('`, `', $columns) . '`';
        } elseif (is_string($columns)) {
            $sqlQuery .= " {$columns}";
        }

        if ($tableName = static::TABLE_NAME) {
            $sqlQuery .= " FROM `{$tableName}`";
        }

        if (!$deleted and $deleteColumn = static::COLUMN_DELETE_TIME) {
            if ($where) {
                $where = "({$where}) AND `{$deleteColumn}` = 0";
            } else {
                $where = "`{$deleteColumn}` = 0";
            }
        }

        if ($where) {
            $sqlQuery .= " WHERE {$where}";
        }

        if ($order) {
            $sqlQuery .= ' ORDER BY';
            foreach ($order as $column => $to) {
                $to = strtoupper($to);
                $sqlQuery .= " `{$column}` {$to},";
            }

            $sqlQuery = rtrim($sqlQuery, ',');
        }

        if ($limit) {
            $sqlQuery .= " LIMIT {$limit}";

            if ($offset) {
                $sqlQuery .= ", {$offset}";
            }
        }

        $database = Application::$instance->db;

        return ($query = $database->prepare($sqlQuery) and $query->execute($binds)) ? $query : null;
    }

    protected static function escape(mixed $value): string
    {
        return match (gettype($value)) {
            'string' => Application::$instance->db->quote($value),
            'NULL' => 'NULL',
            default => strval($value)
        };
    }
}