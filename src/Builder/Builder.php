<?php

namespace WScore\ScoreSql\Builder;

use WScore\ScoreSql\Sql\Sql;
use WScore\ScoreSql\Sql\SqlInterface;

class Builder
{
    /**
     * @var Bind
     */
    protected $bind = null;

    /**
     * @var Quote
     */
    protected $quote = null;

    /**
     * @var Sql
     */
    protected $query;

    /**
     * @var GenericSql
     */
    protected $builder;

    /**
     * database type (mysql, pgsql, etc.) of original query.
     * pass this to the sub-query.
     *
     * @var string
     */
    protected $dbType;

    // +----------------------------------------------------------------------+
    //  construction
    // +----------------------------------------------------------------------+
    /**
     * @param Bind $bind
     * @param Quote $quote
     */
    public function __construct($bind, $quote)
    {
        $this->quote = $quote;
        $this->bind = $bind;
    }

    /**
     * @return Builder
     */
    public static function forge()
    {
        $bind = new Bind();
        $quote = new Quote();
        return new Builder($bind, $quote);
    }

    /**
     * @return Bind
     */
    public function getBind()
    {
        return $this->bind;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * @param Sql|SqlInterface $query
     * @return string
     */
    public function toSql($query)
    {
        $type = $query->magicGet('sqlType');
        $method = 'to' . ucwords($type);
        return $this->$method($query);
    }

    // +----------------------------------------------------------------------+
    //  convert to SQL statements.
    // +----------------------------------------------------------------------+

    /**
     * @param Sql $query
     * @return string
     */
    public function toSelect($query)
    {
        $this->setQuery($query);
        return 'SELECT' . $this->builder->build('select');
    }

    /**
     * @param Sql $query
     */
    protected function setQuery($query)
    {
        $this->query = $query;
        if (!$this->dbType) {
            $this->dbType = $query->magicGet('dbType') ?: 'GenericSql';
            $this->dbType = ucwords($this->dbType);
        }
        /** @var GenericSql $builder */
        $class = '\WScore\ScoreSql\Builder\\' . $this->dbType;
        $this->builder = new $class($this->bind, $this->quote, $this, $this->query);
    }

    /**
     * @param $query
     * @return string
     */
    public function toCount($query)
    {
        $this->setQuery($query);
        return 'SELECT' . $this->builder->build('count');
    }

    /**
     * @param Sql $query
     * @return string
     */
    public function toInsert($query)
    {
        $this->setQuery($query);
        return 'INSERT INTO' . $this->builder->build('insert');
    }

    /**
     * @param Sql $query
     * @return string
     */
    public function toUpdate($query)
    {
        $this->setQuery($query);
        return 'UPDATE' . $this->builder->build('update');
    }

    /**
     * @param Sql $query
     * @return string
     */
    public function toDelete($query)
    {
        $this->setQuery($query);
        return 'DELETE FROM' . $this->builder->build('delete');
    }

    // +----------------------------------------------------------------------+
}