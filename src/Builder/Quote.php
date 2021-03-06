<?php

namespace WScore\ScoreSql\Builder;

use Closure;

class Quote
{
    /**
     * @var string
     */
    protected $format = '"%s"';

    protected $quote1 = '"';

    protected $quote2 = '"';

    /**
     * @param string $q1
     * @param string|null $q2
     */
    public function setQuote($q1, $q2 = null)
    {
        $this->quote1 = $q1;
        $this->quote2 = $q2 ?: $q1;
        $this->format = $q1 . '%s' . $this->quote2;
    }

    /**
     * @param array $list
     * @return array
     */
    public function map($list)
    {
        $list = array_map(function ($val) {
            return $this->quote($val);
        }, $list);
        return $list;
    }

    /**
     * @param string $name
     * @param string|null $prefix
     * @param string|null $parent
     * @return string
     */
    public function quote($name, $prefix = null, $parent = null)
    {
        if (!$name) {
            return $name;
        }
        if ($name instanceof Closure) {
            return $name();
        }
        if ($prefix) {
            $name = $this->addPrefixToName($name, $prefix, $parent);
        }
        return $this->qt($name, [' AS ', ' as ', '.']);
    }

    /**
     * @param $name
     * @param $prefix
     * @param $parent
     * @return string
     */
    protected function addPrefixToName($name, $prefix, $parent)
    {
        if ($this->isQuoted($name)) {
            return $prefix . '.' . $name;
        }
        if (false === strpos($name, '.')) {
            return $prefix . '.' . $name;
        }
        if (substr($name, 0, 2) == '$.' && $parent) {
            return $parent . substr($name, 1);
        }
        return $name;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isQuoted($name)
    {
        if (substr($name, 0, 1) == $this->quote1 &&
            substr($name, -1) == $this->quote2) {
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @param string[] $separator
     * @return string
     */
    protected function qt($name, $separator)
    {
        if (!$separator) {
            return $this->quoteString($name);
        }
        if (!is_array($separator)) {
            $separator = array($separator);
        }
        while ($sep = array_shift($separator)) {
            if (false !== stripos($name, $sep)) {
                $list = explode($sep, $name);
                foreach ($list as $key => $str) {
                    $list[$key] = $this->qt($str, $separator);
                }
                return implode($sep, $list);
            }
        }
        return $this->quoteString($name);
    }

    /**
     * @param $name
     * @return string
     */
    public function quoteString($name)
    {
        if (!$name) {
            return $name;
        }
        if ($name == '*') {
            return $name;
        }
        if ($this->isQuoted($name)) {
            return $name;
        }
        return sprintf($this->format, $name);
    }
}