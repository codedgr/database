<?php

namespace Coded\Database;

class Query extends Controller
{
    function insert($table, array $args, $onDuplicate = false, &$stmt = null)
    {
        $keys = array_map(function ($value) {
            return '`' . $value . '`';
        }, array_keys($args));
        $values = array_keys(static::addColonsToKeys($args));
        $query = 'insert into `' . $table . '` (' . implode(', ', $keys) . ') values (' . implode(', ', $values) . ')';

        $duplicateArgs = [];
        if ($onDuplicate) {
            $duplicateArgs = (is_array($onDuplicate) and $onDuplicate) ? $onDuplicate : $args;
            $set = [];
            $duplicateArgsNew = [];
            foreach ($duplicateArgs as $key => $value) {
                $duplicateKey = 'duplicate' . ucfirst(strtolower($key));
                $set[] = '`' . $key . '` = :' . $duplicateKey;
                $duplicateArgsNew[$duplicateKey] = $value;
            }
            $duplicateArgs = $duplicateArgsNew;
            $query .= ' on duplicate key update ' . implode(', ', $set);
        }

        $query .= ';';
        return $this->q($query, array_merge($args, $duplicateArgs), $stmt);
    }

    function insertIgnore($table, array $args, &$stmt = null)
    {
        $keys = array_map(function ($value) {
            return '`' . $value . '`';
        }, array_keys($args));
        $values = array_keys(static::addColonsToKeys($args));
        $query = 'insert ignore into `' . $table . '` (' . implode(', ', $keys) . ') values (' . implode(', ', $values) . ')';

        $duplicateArgs = [];

        $query .= ';';
        return $this->q($query, array_merge($args, $duplicateArgs), $stmt);
    }

    function update($table, array $args, $filter = null, &$stmt = null)
    {
        $alias = 'a';
        $set = [];
        $argsAlias = [];
        foreach ($args as $key => $value) {
            $set[] = $alias . '.`' . $key . '` = :' . $alias . $key;
            $argsAlias[$alias . $key] = $value;
        }

        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where, $alias);
        $orderQuery = $this->buildOrder($order, $alias);
        $limitQuery = $this->buildLimit($limit);
        $query = 'update `' . $table . '` ' . $alias . ' set ' . implode(', ', $set) . $whereQuery . $orderQuery . $limitQuery . ';';

        if (is_array($where) or is_object($where)) {
            $argsAlias = array_merge($argsAlias, (array)$where);
        }
        return $this->q($query, $argsAlias, $stmt);
    }

    function delete($table, $filter = null, $limit = null, &$stmt = null)
    {
        $alias = 'a';
        $where = $this->buildWhere($filter, $alias);
        $limit = $this->buildLimit($limit);

        $query = 'delete ' . $alias . ' from `' . $table . '` ' . $alias . ' ' . $where . $limit . ';';

        $args = is_array($filter) ? $filter : [];
        return $this->q($query, $args, $stmt);
    }

    function select($table, $filter = null, array $columnsToSelect = [], &$stmt = null, $fetchObject = null)
    {
        $alias = 'a';
        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where, $alias);
        $orderQuery = $this->buildOrder($order, $alias);
        $limitQuery = $this->buildLimit($limit);
        $columnsToSelect = $columnsToSelect ? implode(', ', array_map(function ($value) use ($alias) {
            return $alias . '.`' . $value . '`';
        }, $columnsToSelect)) : $alias . '.*';
        $query = 'select ' . $columnsToSelect . ' from `' . $table . '` ' . $alias . ' ' . $whereQuery . $orderQuery . $limitQuery . ';';

        $args = is_array($where) ? $where : [];
        return $this->q($query, $args, $stmt, $fetchObject);
    }

    function selectRelationship($table, $filter, $relationshipTable, $relationshipFilter, $onKey, &$stmt = null, $fetchObject = null, $reverseOrderPriority = false)
    {
        list($where_a, $order_a, $limit_a) = $this->extract($filter);
        $whereQuery_a = $this->buildWhere($where_a, 'a');
        $orderQuery_a = $this->buildOrder($order_a, 'a');
        $limitQuery_a = $this->buildLimit($limit_a);

        list($where_b, $order_b, $limit_b) = $this->extract($relationshipFilter);
        $whereQuery_b = $this->buildWhere($where_b, 'b');
        $orderQuery_b = $this->buildOrder($order_b, 'b');
        $limitQuery_b = $this->buildLimit($limit_b);

        $whereQueryArray = [];
        if ($whereQuery_a) $whereQueryArray[] = substr($whereQuery_a, 6);
        if ($whereQuery_b) $whereQueryArray[] = substr($whereQuery_b, 6);
        $whereQueryArray[] = 'a.`id` = b.`' . $onKey . '`';
        $whereQuery = implode(' and ', $whereQueryArray);

        $orderQueryArray = [];
        if ($orderQuery_a) $orderQueryArray[] = substr($orderQuery_a, 9);
        if ($orderQuery_b) $orderQueryArray[] = substr($orderQuery_b, 9);
        if ($reverseOrderPriority) array_unshift($orderQueryArray, array_pop($orderQueryArray));
        $orderQuery = '';
        if (array_filter($orderQueryArray)) $orderQuery = ' order by ' . implode(',', $orderQueryArray);

        $limitQuery = $limitQuery_a ?: $limitQuery_b;

        $query = 'select a.* from `' . $table . '` a, `' . $relationshipTable . '` b where ' . $whereQuery . $orderQuery . $limitQuery . ';';

        $args = array_merge($where_a, $where_b);
        return $this->q($query, $args, $stmt, $fetchObject);
    }

    function search($table, $string, array $searchIn, $filter = null, array $columnsToSelect = [], &$stmt = null, $fetchObject = null)
    {
        $alias = 'a';
        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where, $alias);
        $orderQuery = $this->buildOrder($order, $alias);
        $limitQuery = $this->buildLimit($limit);
        $searchQuery = $this->buildSearch($string, $searchIn, $where);
        $columnsToSelect = $columnsToSelect ? implode(', ', array_map(function ($value) use ($alias) {
            return $alias . '.`' . $value . '`';
        }, $columnsToSelect)) : $alias . '.*';
        $query = 'select ' . $columnsToSelect . ' from `' . $table . '` ' . $alias . ' ' . $whereQuery . $searchQuery . $orderQuery . $limitQuery . ';';

        $args = is_array($where) ? $where : [];
        return $this->q($query, $args, $stmt, $fetchObject);
    }

    function searchRelationship($table, $string, array $searchIn, $filter = null, $relationshipTable, $relationshipFilter, $onKey, &$stmt = null, $fetchObject = null, $reverseOrderPriority = false)
    {
        list($where_a, $order_a, $limit_a) = $this->extract($filter);
        $whereQuery_a = $this->buildWhere($where_a, 'a');
        $orderQuery_a = $this->buildOrder($order_a, 'a');
        $limitQuery_a = $this->buildLimit($limit_a);

        list($where_b, $order_b, $limit_b) = $this->extract($relationshipFilter);
        $whereQuery_b = $this->buildWhere($where_b, 'b');
        $orderQuery_b = $this->buildOrder($order_b, 'b');
        $limitQuery_b = $this->buildLimit($limit_b);

        $searchQuery = $this->buildSearch($string, $searchIn, $where, 'a');

        $whereQueryArray = [];
        if ($whereQuery_a) $whereQueryArray[] = substr($whereQuery_a, 6);
        if ($whereQuery_b) $whereQueryArray[] = substr($whereQuery_b, 6);
        $whereQueryArray[] = 'a.`id` = b.`' . $onKey . '`';
        $whereQuery = implode(' and ', $whereQueryArray);

        $orderQueryArray = [];
        if ($orderQuery_a) $orderQueryArray[] = substr($orderQuery_a, 9);
        if ($orderQuery_b) $orderQueryArray[] = substr($orderQuery_b, 9);
        if ($reverseOrderPriority) array_unshift($orderQueryArray, array_pop($orderQueryArray));
        $orderQuery = '';
        if (array_filter($orderQueryArray)) $orderQuery = ' order by ' . implode(',', $orderQueryArray);

        $limitQuery = $limitQuery_a ?: $limitQuery_b;

        $query = 'select a.* from `' . $table . '` a, `' . $relationshipTable . '` b where ' . $whereQuery . $searchQuery . $orderQuery . $limitQuery . ';';

        $args = array_merge($where_a, $where_b);
        return $this->q($query, $args, $stmt, $fetchObject);
    }

    function single($table, $filter = null, array $columnsToSelect = [], &$stmt = null, $fetchObject = null)
    {
        if (is_numeric($filter)) {
            $id = $filter;
            $filter = [];
            $filter['id'] = $id;
            $filter['limit'] = 1;
        } elseif (is_string($filter)) {
            if (strpos($filter, 'limit') !== false) {

            } else {
                $filter .= 'limit 1';
            }
        } else {
            $filter['limit'] = 1;
        }
        $result = $this->select($table, $filter, $columnsToSelect, $stmt, $fetchObject);
        if (isset($result[0])) return $result[0];
        return $result;
    }

    function count($table, $filter = null, &$stmt = null)
    {
        $alias = 'a';
        $where = $this->buildWhere($filter, $alias);
        $query = 'select count(*) as c from `' . $table . '` ' . $alias . ' ' . $where . ';';

        $args = is_array($filter) ? $filter : [];
        return $this->q($query, $args, $stmt)[0]->c;
    }

    function math($math, $column, $table, $filter = null, &$stmt = null)
    {
        $alias = 'a';
        $where = $this->buildWhere($filter, $alias);
        $query = 'select ' . $math . '(' . $alias . '.`' . $column . '`) as c from `' . $table . '` ' . $alias . ' ' . $where . ';';

        $args = is_array($filter) ? $filter : [];
        return $this->q($query, $args, $stmt)[0]->c;
    }

    function sum($column, $table, $filter = null, &$stmt = null)
    {
        return $this->math('sum', $column, $table, $filter, $stmt);
    }

    function avg($column, $table, $filter = null, &$stmt = null)
    {
        return $this->math('avg', $column, $table, $filter, $stmt);
    }

    function min($column, $table, $filter = null, &$stmt = null)
    {
        return $this->math('min', $column, $table, $filter, $stmt);
    }

    function max($column, $table, $filter = null, &$stmt = null)
    {
        return $this->math('max', $column, $table, $filter, $stmt);
    }

    protected function extract($filter)
    {
        $order = $limit = [];
        if (is_string($filter) or is_numeric($filter)) return [$filter, $order, $limit];

        if (isset($filter['order'])) {
            $order = $filter['order'];
            unset($filter['order']);
        }
        if (isset($filter['limit'])) {
            $limit = $filter['limit'];
            unset($filter['limit']);
        }
        $where = $filter;
        return [$where, $order, $limit];
    }

    protected function buildSearch($string, array $searchIn, &$where, $alias = 'a')
    {
        if (!strlen($string) or !count($searchIn)) return '';
        $hadWhete = count($where);
        $data = [];
        foreach (explode(' ', trim($string)) as $word) {
            foreach ($searchIn as $key) {
                $customKey = md5('search' . $alias . $key . $word . microtime() . rand(1, 9999999));
                $data[] = $alias . '.`' . $key . '` like :' . $alias . $customKey;
                $where[$customKey] = '%' . $word . '%';
            }
        }

        return ($hadWhete ? ' and ' : ' where ') . '(' . implode(' or ', $data) . ')';
    }

    protected function buildWhere(&$where, $alias = 'a')
    {
        $keyAlias = 'w' . $alias;
        if (!$where) return '';
        if (is_numeric($where)) {
            $input = trim($where);
            $where = [];
            $where[$keyAlias . 'id'] = $input;
            return ' where ' . $alias . '.id = :' . $keyAlias . 'id';
        }
        if (is_string($where)) return ' where ' . trim($where);

        $data = $args = [];
        foreach ($where as $key => $value) {
            if (is_array($value) and in_array(strtolower($value[0]), ['=', '!=', '>', '<', '>=', '<=', 'like', 'not like'])) {
                $data[] = $alias . '.`' . $key . '` ' . $value[0] . ' :' . $keyAlias . $key;
                $args[$keyAlias . $key] = $value[1];
                continue;
            } elseif (is_array($value) and in_array(strtolower($value[0]), ['in', 'not in']) and is_array($value[1])) {
                $special = strtolower($value[0]) == 'in' ? 'In' : 'NotIn';
                $inKeys = [];
                foreach ($value[1] as $k => $v) {
                    do {
                        $rand = $key . $special . ucfirst(strtolower($k));
                    } while (in_array($rand, $inKeys));
                    $inKeys[] = $keyAlias . $rand;
                    $args[$keyAlias . $rand] = $v;
                }
                $inKeys = array_map(function ($value) {
                    return ':' . $value;
                }, $inKeys);
                $data[] = $alias . '.`' . $key . '` ' . $value[0] . ' (' . implode(', ', $inKeys) . ')';
                continue;
            } elseif (is_array($value) and in_array(strtolower($value[0]), ['between']) and is_array($value[1]) and count($value[1]) == 2) {
                $inKeys = [];
                foreach ($value[1] as $k => $v) {
                    do {
                        $rand = $key . 'Between' . ucfirst(strtolower($k));
                    } while (in_array($rand, $inKeys));
                    $inKeys[] = $keyAlias . $rand;
                    $args[$keyAlias . $rand] = $v;
                }
                $inKeys = array_map(function ($value) {
                    return ':' . $value;
                }, $inKeys);
                $data[] = $alias . '.`' . $key . '` ' . $value[0] . ' ' . implode(' and ', $inKeys);
                continue;
            } elseif (is_string($value) and strlen($value) and ($value[0] == '%' or $value[strlen($value) - 1] == '%')) {
                $data[] = $alias . '.`' . $key . '` like :' . $keyAlias . $key;
            } elseif ($value === null) {
                $data[] = $alias . '.`' . $key . '` is null';
            } else {
                $data[] = $alias . '.`' . $key . '` = :' . $keyAlias . $key;
            }
            if ($value !== null) $args[$keyAlias . $key] = $value;
        }
        $where = $args;
        return ' where ' . implode(' and ', $data);
    }

    protected function buildOrder($order, $alias = 'a')
    {
        if ($order === false or $order === null or $order === []) return '';
        if (is_array($order)) {
            $return = [];
            if (array_key_exists(0, $order)) {
                $return = array_map(function ($value) use ($alias) {
                    return $alias . '.`' . $value . '`';
                }, $order);
            } else {
                foreach ($order as $key => $by) {
                    $return[] = $alias . '.`' . $key . '` ' . $by;
                }
            }
            return ' order by ' . implode(',', $return);
        }
        return ' order by ' . trim($order);
    }

    protected function buildLimit($limit)
    {
        if ($limit === false or $limit === null or $limit === []) return '';
        if (is_array($limit)) return ' limit ' . implode(',', $limit);
        return ' limit ' . trim($limit);
    }
}
