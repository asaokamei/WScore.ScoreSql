<?php

namespace tests\Sql;

use PHPUnit\Framework\TestCase;
use WScore\ScoreSql\Builder\Bind;

require_once(dirname(__DIR__) . '/autoloader.php');

class Bind_Test extends TestCase
{
    /**
     * @var Bind
     */
    var $b;

    function setup(): void
    {
        $this->b = new Bind();
    }

    function test0()
    {
        $this->assertEquals('WScore\ScoreSql\Builder\Bind', get_class($this->b));
    }

    /**
     * @test
     */
    function prepare_replaces_value_with_holder_and_saves_it()
    {
        $value = $this->get();
        $holder = $this->b->prepare($value);
        $bind = $this->b->getBinding();

        $this->assertTrue(isset($bind[$holder]));
        $this->assertEquals($value, $bind[$holder]);
    }

    function get($head = 'value')
    {
        return $head . mt_rand(1000, 9999);
    }

    /**
     * @test
     */
    function prepare_ignores_callable_value()
    {
        $value = $this->get();
        $val = function () use ($value) {
            return $value;
        };
        $holder = $this->b->prepare($val);
        $bind = $this->b->getBinding();

        $this->assertTrue(is_callable($holder));
        $this->assertEquals($value, $val());
        $this->assertTrue(empty($bind));
    }

    /**
     * @test
     */
    function with_type_and_setting_type()
    {
        $type1 = $this->get('type');
        $type2 = $this->get('type');
        $this->b->setColumnType('col2', $type2);
        $holder1 = $this->b->prepare('test', null, $type1);
        $holder2 = $this->b->prepare('test', 'col2');

        $types = $this->b->getBindType();
        $this->assertEquals($type1, $types[$holder1]);
        $this->assertEquals($type2, $types[$holder2]);
    }
}
