<?php

namespace WScore\ScoreSql;

use WScore\ScoreSql\Sql\Join;
use WScore\ScoreSql\Sql\Where;

/**
 * Class Query
 *
 * @package WScore\ScoreSql
 *
 */
class DB
{
    // +----------------------------------------------------------------------+
    //  manage objects, aka Facade.
    // +----------------------------------------------------------------------+
    /**
     * @param string $table
     * @param string|null $alias
     * @return Query
     */
    public static function from($table, $alias = null)
    {
        $self = Query::forge();
        $self->table($table, $alias);
        return $self;
    }

    /**
     * @param string $table
     * @param string|null $alias
     * @return Join
     */
    public static function join($table, $alias = null)
    {
        return new Join($table, $alias);
    }

    /**
     * @param string|null $column
     * @return Where
     */
    public static function given($column = null)
    {
        return Where::column($column);
    }
}
