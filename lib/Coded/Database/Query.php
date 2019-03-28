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

    function update($table, array $args, $filter = null, &$stmt = null)
    {
        $set = [];
        foreach ($args as $key => $value) {
            $set[] = '`' . $key . '` = :' . $key;
        }

        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where);
        $orderQuery = $this->buildOrder($order);
        $limitQuery = $this->buildLimit($limit);
        $query = 'update `' . $table . '` set ' . implode(', ', $set) . $whereQuery . $orderQuery . $limitQuery . ';';

        if (is_array($where) or is_object($where)) {
            $args = array_merge($args, (array)$where);
        }
        return $this->q($query, $args, $stmt);
    }

    function delete($table, $filter = null, $limit = null, &$stmt = null)
    {
        $where = $this->buildWhere($filter);
        $limit = $this->buildLimit($limit);

        $query = 'delete from `' . $table . '`' . $where . $limit . ';';

        $args = is_array($filter) ? $filter : [];
        return $this->q($query, $args, $stmt);
    }

    function select($table, $filter = null, array $columnsToSelect = [], &$stmt = null, $fetchObject = null)
    {
        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where);
        $orderQuery = $this->buildOrder($order);
        $limitQuery = $this->buildLimit($limit);
        $columnsToSelect = $columnsToSelect ? implode(', ', array_map(function ($value) {
            return '`' . $value . '`';
        }, $columnsToSelect)) : '*';
        $query = 'select ' . $columnsToSelect . ' from `' . $table . '`' . $whereQuery . $orderQuery . $limitQuery . ';';

        $args = is_array($where) ? $where : [];
        return $this->q($query, $args, $stmt, $fetchObject);
    }

    function search($table, $string, array $searchIn, $filter = null, array $columnsToSelect = [], &$stmt = null, $fetchObject = null)
    {
        list($where, $order, $limit) = $this->extract($filter);
        $whereQuery = $this->buildWhere($where);
        $orderQuery = $this->buildOrder($order);
        $limitQuery = $this->buildLimit($limit);
        $searchQuery = $this->buildSearch($string, $searchIn, $where);
        $columnsToSelect = $columnsToSelect ? implode(', ', array_map(function ($value) {
            return '`' . $value . '`';
        }, $columnsToSelect)) : '*';
        $query = 'select ' . $columnsToSelect . ' from `' . $table . '`' . $whereQuery . $searchQuery . $orderQuery . $limitQuery . ';';

        $args = is_array($where) ? $where : [];
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
        $where = $this->buildWhere($filter);
        $query = 'select count(*) as c from `' . $table . '`' . $where . ';';

        $args = is_array($filter) ? $filter : [];
        return $this->q($query, $args, $stmt)[0]->c;
    }

    function math($math, $column, $table, $filter = null, &$stmt = null)
    {
        $where = $this->buildWhere($filter);
        $query = 'select ' . $math . '(`' . $column . '`) as c from `' . $table . '`' . $where . ';';

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

    protected function buildSearch($string, array $searchIn, &$where)
    {
        if (!strlen($string) or !count($searchIn)) return '';
        $hadWhete = count($where);
        $data = [];
        foreach (explode(' ', trim($string)) as $word) {
            foreach ($searchIn as $key) {
                $customKey = md5('search' . $key . $word . microtime() . rand(1, 9999999));
                $data[] = '`' . $key . '` like :' . $customKey;
                $where[$customKey] = '%' . $word . '%';
            }
        }

        return ($hadWhete ? ' and ' : ' where ') . '(' . implode(' or ', $data) . ')';
    }

    protected function buildWhere(&$where)
    {
        if (!$where) return '';
        if (is_numeric($where)) {
            $input = trim($where);
            $where = [];
            $where['id'] = $input;
            return ' where id = :id';
        }
        if (is_string($where)) return ' where ' . trim($where);

        $data = $args = [];
        foreach ($where as $key => $value) {
            if (is_array($value) and in_array(strtolower($value[0]), ['=', '!=', '>', '<', '>=', '<=', 'like', 'not like'])) {
                $data[] = '`' . $key . '` ' . $value[0] . ' :w' . $key;
                $args['w' . $key] = $value[1];
                continue;
            } elseif (is_array($value) and in_array(strtolower($value[0]), ['in', 'not in']) and is_array($value[1])) {
                $special = strtolower($value[0]) == 'in' ? 'In' : 'NotIn';
                $inKeys = [];
                foreach ($value[1] as $k => $v) {
                    do {
                        $rand = $key . $special . ucfirst(strtolower($k));
                    } while (in_array($rand, $inKeys));
                    $inKeys[] = 'w' . $rand;
                    $args['w' . $rand] = $v;
                }
                $inKeys = array_map(function ($value) {
                    return ':' . $value;
                }, $inKeys);
                $data[] = '`' . $key . '` ' . $value[0] . ' (' . implode(', ', $inKeys) . ')';
                continue;
            } elseif (is_array($value) and in_array(strtolower($value[0]), ['between']) and is_array($value[1]) and count($value[1]) == 2) {
                $inKeys = [];
                foreach ($value[1] as $k => $v) {
                    do {
                        $rand = $key . 'Between' . ucfirst(strtolower($k));
                    } while (in_array($rand, $inKeys));
                    $inKeys[] = 'w' . $rand;
                    $args['w' . $rand] = $v;
                }
                $inKeys = array_map(function ($value) {
                    return ':' . $value;
                }, $inKeys);
                $data[] = '`' . $key . '` ' . $value[0] . ' ' . implode(' and ', $inKeys);
                continue;
            } elseif (is_string($value) and strlen($value) and ($value[0] == '%' or $value[strlen($value) - 1] == '%')) {
                $data[] = '`' . $key . '` like :w' . $key;
            } elseif ($value === null) {
                $data[] = '`' . $key . '` is null';
            } else {
                $data[] = '`' . $key . '` = :w' . $key;
            }
            $args['w' . $key] = $value;
        }
        $where = $args;
        return ' where ' . implode(' and ', $data);
    }

    protected function buildOrder($order)
    {
        if ($order === false or $order === null or $order === []) return '';
        if (is_array($order)) {
            $return = [];
            if (array_key_exists(0, $order)) {
                $return = array_map(function ($value) {
                    return '`' . $value . '`';
                }, $order);
            } else {
                foreach ($order as $key => $by) {
                    $return[] = '`' . $key . '` ' . $by;
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
