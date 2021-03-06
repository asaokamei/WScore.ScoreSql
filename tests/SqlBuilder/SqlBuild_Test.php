<?php

namespace tests\Sql;

use PHPUnit\Framework\TestCase;
use WScore\ScoreSql\Builder\Builder;
use WScore\ScoreSql\DB;
use WScore\ScoreSql\Sql\Sql;
use WScore\ScoreSql\Sql\Where;

require_once(dirname(__DIR__) . '/autoloader.php');

class SqlBuild_Test extends TestCase
{
    /**
     * @var Builder
     */
    var $builder;

    /**
     * @var Sql
     */
    var $query;

    function setup(): void
    {
        $this->builder = Builder::forge();
        $this->query = new Sql();
    }

    function test0()
    {
        $this->assertEquals('WScore\ScoreSql\Builder\Builder', get_class($this->builder));
        $this->assertEquals('WScore\ScoreSql\Sql\Sql', get_class($this->query));
    }

    /**
     * @test
     */
    function insert()
    {
        $value = $this->get();
        $this->query->table('testTable')->value('testCol', $value);
        $sql = $this->builder->toInsert($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals('INSERT INTO "testTable" ( "testCol" ) VALUES ( :db_prep_1 )', $sql);
        $this->assertEquals($value, $bind[':db_prep_1']);
        $this->assertCount(1, $bind);
    }

    function get($head = 'value')
    {
        return $head . mt_rand(1000, 9999);
    }

    /**
     * @test
     */
    function update()
    {
        $values = [
            'testCol' => $this->get(),
            'moreCol' => $this->get(),
        ];
        $keyVal = $this->get();
        $this->query->table('testTable')->value($values)->where(
            Where::column('pKey')->eq($keyVal)
        );
        $sql = $this->builder->toUpdate($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'UPDATE "testTable" SET "testCol"=:db_prep_1, "moreCol"=:db_prep_2 WHERE "pKey" = :db_prep_3',
            $sql);
        $this->assertEquals($keyVal, $bind[':db_prep_3']);
        $this->assertEquals($values['testCol'], $bind[':db_prep_1']);
        $this->assertEquals($values['moreCol'], $bind[':db_prep_2']);
        $this->assertCount(3, $bind);
    }

    /**
     * @test
     */
    function delete()
    {
        $keyVal = $this->get();
        $this->query->table('testTable')->where(
            Where::column('pKey')->eq($keyVal)
        );
        $sql = $this->builder->toDelete($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'DELETE FROM "testTable" WHERE "pKey" = :db_prep_1',
            $sql);
        $this->assertEquals($keyVal, $bind[':db_prep_1']);
        $this->assertCount(1, $bind);
    }

    /**
     * @test
     */
    function select()
    {
        $this->query
            ->table('testTable')
            ->column('colTest', 'aliasAs')
            ->where(Where::column('"my table".name')->like('bob'))
            ->order('pKey');
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT ' . '"colTest" AS "aliasAs" FROM "testTable" ' .
            'WHERE "my table"."name" LIKE :db_prep_1 ORDER BY "pKey" ASC',
            $sql);
        $this->assertEquals('bob', $bind[':db_prep_1']);
        $this->assertCount(1, $bind);
    }

    /**
     * @test
     */
    function select_in()
    {
        $in = [
            $this->get(),
            $this->get(),
        ];
        $this->query
            ->table('testTable')
            ->where(Where::column('name')->contain('bob')->status->in($in))
            ->order('pKey');
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT ' . '* FROM "testTable" ' .
            'WHERE "name" LIKE :db_prep_1 AND "status" IN ( :db_prep_2, :db_prep_3 ) ' .
            'ORDER BY "pKey" ASC',
            $sql);
        $this->assertEquals('%bob%', $bind[':db_prep_1']);
        $this->assertEquals($in[0], $bind[':db_prep_2']);
        $this->assertEquals($in[1], $bind[':db_prep_3']);
        $this->assertCount(3, $bind);
    }

    /**
     * @test
     */
    function select_between()
    {
        $this->query
            ->table('testTable')
            ->where(Where::column('value')->between(123, 345))
            ->order('pKey');
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT ' . '* FROM "testTable" ' .
            'WHERE "value" BETWEEN :db_prep_1 AND :db_prep_2 ' .
            'ORDER BY "pKey" ASC',
            $sql);
        $this->assertEquals('123', $bind[':db_prep_1']);
        $this->assertEquals('345', $bind[':db_prep_2']);
        $this->assertCount(2, $bind);
    }

    /**
     * @test
     */
    function select_isNull_and_no_value_will_be_bound()
    {
        $this->query
            ->table('testTable')
            ->where(Where::column('value')->isNull());
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT * FROM "testTable" ' .
            'WHERE "value" IS NULL',
            $sql);
        $this->assertEmpty($bind);
    }

    /**
     * @test
     */
    function select_isNotNull_and_no_value_will_be_bound()
    {
        $this->query
            ->table('testTable')
            ->where(Where::column('value')->notNull());
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT * FROM "testTable" ' .
            'WHERE "value" IS NOT NULL',
            $sql);
        $this->assertEmpty($bind);
    }

    /**
     * @test
     */
    function select_complex_case()
    {
        $this->query
            ->table('testTable', 'aliasTable')
            ->forUpdate()
            ->distinct()
            ->column('colTest', 'aliasAs')
            ->where(Where::column('name')->contain('bob'))
            ->having(Where::column(Sql::raw('COUNT(*)'))->gt(5))
            ->group('grouped')
            ->order('pKey')
            ->order('status', 'desc')
            ->order('t1.order')
            ->limit(5)
            ->offset(10);
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT DISTINCT "colTest" AS "aliasAs" ' .
            'FROM "testTable" AS "aliasTable" WHERE "aliasTable"."name" LIKE :db_prep_1 ' .
            'GROUP BY "grouped" HAVING COUNT(*) > :db_prep_2 ' .
            'ORDER BY "aliasTable"."pKey" ASC, "aliasTable"."status" desc, "t1"."order" ASC ' .
            'LIMIT 5 OFFSET 10 FOR UPDATE',
            $sql);
        $this->assertEquals('%bob%', $bind[':db_prep_1']);
    }

    /**
     * @test
     */
    function select_using_multiple_set_where()
    {
        $this->query
            ->table('testTable')
            ->where(Where::column('value')->isNull())
            ->where(Where::column('value')->eq(''), 'or');
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT * FROM "testTable" ' .
            'WHERE "value" IS NULL OR "value" = :db_prep_1',
            $sql);
        $this->assertEquals('', $bind[':db_prep_1']);
    }

    /**
     * @test
     */
    function left_join_using_key()
    {
        $this->query->table('testTable', 'tt')
            ->where(Where::column('test')->eq('tested'))
            ->join(DB::join('anotherOne', 'ao')->left()->using('pKey'));
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT ' . '* FROM "testTable" AS "tt" ' .
            'LEFT OUTER JOIN "anotherOne" "ao" USING( "pKey" ) ' .
            'WHERE "tt"."test" = :db_prep_1',
            $sql);
        $this->assertCount(1, $bind);
        $this->assertEquals('tested', $bind[':db_prep_1']);
    }

    /**
     * @test
     */
    function join_using_and_on()
    {
        $this->query->table('testTable', 'tt')
            ->where(Where::column('test')->eq('tested'))
            ->join(
                DB::join('anotherOne', 'ao')
                    ->left()
                    ->using('pKey')->
                    on(Where::column('status')->eq('1'))
            );
        $sql = $this->builder->toSelect($this->query);
        $bind = $this->builder->getBind()->getBinding();
        $this->assertEquals(
            'SELECT * FROM "testTable" AS "tt" ' .
            'LEFT OUTER JOIN "anotherOne" "ao" ' .
            'ON ( "ao"."pKey"="tt"."pKey" AND ( "ao"."status" = :db_prep_1 ) ) ' .
            'WHERE "tt"."test" = :db_prep_2',
            $sql);
        $this->assertCount(2, $bind);
        $this->assertEquals('1', $bind[':db_prep_1']);
        $this->assertEquals('tested', $bind[':db_prep_2']);
    }
}
