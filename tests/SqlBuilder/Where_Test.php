<?php
namespace tests\Sql;

use WScore\SqlBuilder\Builder\Bind;
use WScore\SqlBuilder\Builder\Quote;
use WScore\SqlBuilder\Sql\Where;

require_once( dirname( __DIR__ ) . '/autoloader.php' );

class Where_Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \WScore\SqlBuilder\Sql\Where
     */
    var $w;

    function setup()
    {
        Bind::reset();
        $this->w = new Where();
    }

    function test0()
    {
        $this->assertEquals( 'WScore\SqlBuilder\Sql\Where', get_class( $this->w ) );
    }

    // +----------------------------------------------------------------------+
    //  test various methods
    // +----------------------------------------------------------------------+
    /**
     * @test
     */
    function where_using_call()
    {
        $this->w
            ->eq->eq( 'eq' )
            ->ne->ne( 'ne' )
            ->lt->lt( 'lt' )
            ->gt->gt( 'gt' )
            ->le->le( 'le' )
            ->ge->ge( 'ge' );
        $where = $this->w->getCriteria();
        $this->assertEquals( [ 'col' => 'eq', 'val' => 'eq', 'rel' => '=',  'op' => 'AND' ], $where[ 0 ] );
        $this->assertEquals( [ 'col' => 'ne', 'val' => 'ne', 'rel' => '!=', 'op' => 'AND' ], $where[ 1 ] );
        $this->assertEquals( [ 'col' => 'lt', 'val' => 'lt', 'rel' => '<',  'op' => 'AND' ], $where[ 2 ] );
        $this->assertEquals( [ 'col' => 'gt', 'val' => 'gt', 'rel' => '>',  'op' => 'AND' ], $where[ 3 ] );
        $this->assertEquals( [ 'col' => 'le', 'val' => 'le', 'rel' => '<=', 'op' => 'AND' ], $where[ 4 ] );
        $this->assertEquals( [ 'col' => 'ge', 'val' => 'ge', 'rel' => '>=', 'op' => 'AND' ], $where[ 5 ] );
    }

    /**
     * @test
     */
    function where_in_and_notIn()
    {
        $this->w->test->in( 'tested', 'more' )->more->notIn( 'good', 'bad' );
        $sql = $this->w->build( $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '"test" IN ( :db_prep_1, :db_prep_2 ) AND "more" NOT IN ( :db_prep_3, :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'more', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function where_contain_startWith_end_with()
    {
        $this->w->
            test->contain( 'contains' )->
            more->startWith( 'starts' )->
            some->endWith( 'ends' );
        $sql = $this->w->build( $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '"test" LIKE :db_prep_1 AND "more" LIKE :db_prep_2 AND "some" LIKE :db_prep_3',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 3, count( $bound ) );
        $this->assertEquals( '%contains%', $bound[':db_prep_1'] );
        $this->assertEquals( 'starts%', $bound[':db_prep_2'] );
        $this->assertEquals( '%ends', $bound[':db_prep_3'] );
    }

    /**
     * @test
     */
    function where_isNull_and_notNull()
    {
        $this->w->test->isNull()->more->notNull();
        $sql = $this->w->build( $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '"test" IS NULL AND "more" IS NOT NULL',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 0, count( $bound ) );
    }

    /**
     * @test
     */
    function or_makes_or()
    {
        $sql = Where::column('test')->eq('tested')->or()->more->ne('moreD')->build();
        $this->assertEquals( 'test = tested OR more != moreD', $sql );
    }

    // +----------------------------------------------------------------------+
    //  testing blocks
    // +----------------------------------------------------------------------+
    /**
     * @test
     */
    function and_or_and()
    {
        $this->w
            ->set(
                Where::column( 'test' )->eq( 'tested' )->more->eq( 'moreD' )
            )
            ->or()->set(
                Where::column( 'test' )->eq( 'good' )->more->eq( 'bad' )
            );
        $sql = $this->w->build( $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 AND "more" = :db_prep_2 ) OR ( "test" = :db_prep_3 AND "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function and_or_and_using_setting_where()
    {
        $this->w
            ->set(
                Where::column( 'test' )->eq( 'tested' )->more->eq( 'moreD' )
            )
            ->set(
                Where::column( 'test' )->eq( 'good' )->more->eq( 'bad' ), 'or'
            );
        $sql = $this->w->build( $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 AND "more" = :db_prep_2 ) OR ( "test" = :db_prep_3 AND "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function or_and_or()
    {
        $this->w
            ->set(
                Where::column( 'test' )->eq( 'tested' )->or()->more->eq( 'moreD' )
            )
            ->set(
                Where::column( 'test' )->eq( 'good' )->or()->more->eq( 'bad' )
            );
        $sql = $this->w->build(  $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 OR "more" = :db_prep_2 ) AND ( "test" = :db_prep_3 OR "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function block_or_and_or()
    {
        $this->w
            ->startBlock()
                ->test->eq('tested')->or()->more->eq('moreD')
            ->endBlock()
            ->startBlock()
                ->test->eq('good')->or()->more->eq('bad')
            ->endBlock();
        $sql = $this->w->build(  $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 OR "more" = :db_prep_2 ) AND ( "test" = :db_prep_3 OR "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function block_and_or_and()
    {
        $this->w
            ->startBlock()
            ->test->eq('tested')->and()->more->eq('moreD')
            ->endBlock()
            ->orBlock()
            ->test->eq('good')->and()->more->eq('bad')
            ->endBlock();
        $sql = $this->w->build(  $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 AND "more" = :db_prep_2 ) OR ( "test" = :db_prep_3 AND "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * probably, this is the correct behavior, when forget to 
     * end the block.  
     * 
     * @test
     */
    function block_without_endBlock()
    {
        $w = $this->w
            ->startBlock()
            ->test->eq('tested')->or()->more->eq('moreD')
            ->endBlock()
            ->startBlock()
            ->test->eq('good')->or()->more->eq('bad');
        $w = $w->getRootParent();
        $sql = $w->build(  $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 OR "more" = :db_prep_2 ) AND "test" = :db_prep_3 OR "more" = :db_prep_4',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    /**
     * @test
     */
    function blockSoFar()
    {
        $w = $this->w
            ->test->eq('tested')->more->eq('moreD')
            ->packBlock()
            ->orBlock()
            ->test->eq('good')->more->eq('bad')
            ->endBlock();
        $sql = $w->build(  $bind=new Bind(), new Quote() );
        $this->assertEquals(
            '( "test" = :db_prep_1 AND "more" = :db_prep_2 ) OR ( "test" = :db_prep_3 AND "more" = :db_prep_4 )',
            $sql
        );
        $bound = $bind->getBinding();
        $this->assertEquals( 4, count( $bound ) );
        $this->assertEquals( 'tested', $bound[':db_prep_1'] );
        $this->assertEquals( 'moreD', $bound[':db_prep_2'] );
        $this->assertEquals( 'good', $bound[':db_prep_3'] );
        $this->assertEquals( 'bad', $bound[':db_prep_4'] );
    }

    // +----------------------------------------------------------------------+
}
