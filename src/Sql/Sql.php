<?php

namespace WScore\ScoreSql\Sql;

use Closure;

class Sql implements SqlInterface
{
    /**
     * @var string
     */
    protected $sqlType = 'select';

    /**
     * @var string
     */
    protected $dbType = 'GenericSql';

    /**
     * @var Where
     */
    protected $where;

    /**
     * @var string           name of database table
     */
    protected $table;

    /**
     * @var string           name of aliased table name
     */
    protected $tableAlias;

    /**
     * @var string           name of table of parent query (for sub-query)
     */
    protected $tableParent;

    /**
     * @var string           name of id (primary key)
     */
    protected $keyName;

    /**
     * @var Join[]           join for table
     */
    protected $join = [];

    /**
     * @var string|array     columns to select in array or string
     */
    protected $columns = [];

    /**
     * @var array            values for insert/update in array
     */
    protected $values = [];

    /**
     * @var string[]         such as distinct, for update, etc.
     */
    protected $selFlags = [];

    /**
     * @var array            order by. [ [ order, dir ], [].. ]
     */
    protected $order = [];

    /**
     * @var array           group by. [ group, group2, ...]
     */
    protected $group = [];

    /**
     * @var Where
     */
    protected $having;

    /**
     * @var int
     */
    protected $limit = null;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var string
     */
    protected $returning;

    /**
     * @var bool
     */
    protected $forUpdate = false;

    protected $subQueryCount = 1;

    // +----------------------------------------------------------------------+

    /**
     * @param $value
     * @return Closure
     */
    public static function raw($value)
    {
        return function () use ($value) {
            return $value;
        };
    }

    /**
     * @param string $type
     * @return $this
     */
    public function dbType($type)
    {
        $this->dbType = $type;
        return $this;
    }

    /**
     * starts a Where clause with column name set.
     *
     * @param $column
     * @return Where
     */
    public function __get($column)
    {
        return $this->given($column);
    }

    /**
     * set values (key&val) for insert or update.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * @param string|Closure $column
     * @return Where
     */
    public function given($column)
    {
        return Where::column($column);
    }

    /**
     *
     */
    public function reset()
    {
        $this->sqlType = 'select';
        $this->where = null;
        $this->join = [];
        $this->columns = [];
        $this->values = [];
        $this->selFlags = [];
        $this->order = [];
        $this->group = [];
        $this->having = null;
        $this->limit = null;
        $this->offset = 0;
        $this->returning = null;
        $this->forUpdate = false;
    }

    /**
     * return a new Sql|Query object as sub-query.
     *
     * @param string $table
     * @param string|null $alias
     * @return $this
     */
    public function subQuery($table, $alias = null)
    {
        $sub = new self();
        if (!$alias) {
            $alias = 'sub_' . $this->subQueryCount++;
        }
        $sub->table($table, $alias);
        $sub->setParentTable($this->getAliasOrTable());
        return $sub;
    }

    /**
     * @param string|null $table
     * @param string|null $alias
     * @return $this
     */
    public function table($table, $alias = null)
    {
        $this->table = $this->table = $table;
        $this->tableAlias = $alias ?: null;
        return $this;
    }

    /**
     * for sub query; sets the parent query's table name.
     *
     * @param string $name
     */
    protected function setParentTable($name)
    {
        $this->tableParent = $name;
    }

    /**
     * @return string
     */
    protected function getAliasOrTable()
    {
        return $this->tableAlias ?: $this->table;
    }

    /**
     * @param $key
     * @return string|array|mixed
     */
    public function magicGet($key)
    {
        return isset($this->$key) ? $this->$key : null;
    }

    /**
     * @param $where
     * @return $this
     */
    public function whereOr($where)
    {
        return $this->where($where, 'or');
    }

    // +----------------------------------------------------------------------+
    //  Setting string, array, and data to build SQL statement.
    // +----------------------------------------------------------------------+

    /**
     * @param Where $where
     * @param string|null $andOr
     * @return $this
     */
    public function where($where, $andOr = null)
    {
        if (!$this->where) {
            $this->where = $where;
        } else {
            $this->where->set($where, $andOr);
        }
        return $this;
    }

    /**
     * @param JoinInterface $join
     * @return $this
     */
    public function join($join)
    {
        $this->join[] = $join;
        $join->setQueryTable($this->getAliasOrTable());
        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * @param string $keyName
     * @return $this
     */
    public function keyName($keyName)
    {
        $this->keyName = $keyName;
        return $this;
    }

    /**
     * @param string $id
     * @param string|null $column
     * @return $this
     */
    public function key($id, $column = null)
    {
        if (!$id) {
            return $this;
        }
        $column = $column ?: $this->keyName;
        $this->where($this->$column->eq($id));
        return $this;
    }

    /**
     * @param string $column
     * @param null|string $as
     * @return $this
     */
    public function column($column, $as = null)
    {
        if ($column === false) {
            $this->columns = [];
        } else {
            if ($as) {
                $this->columns[$as] = $column;
            } else {
                $this->columns[] = $column;
            }
        }
        return $this;
    }

    /**
     * ->columns( [ 'col1', 'col2', ...] )
     * or
     * ->columns( 'col1', 'col2', ... );
     *
     * @param array|string $column
     * @return $this
     */
    public function columns($column)
    {
        if (is_array($column)) {
            $this->columns += $column;
        } elseif (func_num_args() > 1) {
            $column = func_get_args();
            $this->columns += $column;
        } else {
            $this->columns += [$column];
        }
        return $this;
    }

    /**
     * set values for insert or update.
     *
     * @param string|array $name
     * @param string|null $value
     * @return $this
     */
    public function value($name, $value = null)
    {
        if (is_array($name)) {
            $this->values += $name;
        } elseif (func_num_args() > 1) {
            $this->values[$name] = $value;
        }
        return $this;
    }

    /**
     * @param string $order
     * @param string $sort
     * @return $this
     */
    public function order($order, $sort = 'ASC')
    {
        $this->order[] = [$order, $sort];
        return $this;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function group($group)
    {
        $this->group[] = $group;
        return $this;
    }

    /**
     * @param Where $having
     * @return $this
     */
    public function having($having)
    {
        $this->having = $having;
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (is_numeric($limit)) ? (int)$limit : null;
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (is_numeric($offset)) ? (int)$offset : 0;
        return $this;
    }

    /**
     * creates SELECT DISTINCT statement.
     * @return $this
     */
    public function distinct()
    {
        return $this->flag('DISTINCT');
    }

    /**
     * @param string $flag
     * @return $this
     */
    public function flag($flag)
    {
        $this->selFlags[] = $flag;
        return $this;
    }

    /**
     * @param bool $for
     * @return $this
     */
    public function forUpdate($for = true)
    {
        $this->forUpdate = $for;
        return $this;
    }

    /**
     * @param string $return
     * @return $this
     */
    public function returning($return)
    {
        $this->returning = $return;
        return $this;
    }
    // +----------------------------------------------------------------------+
}