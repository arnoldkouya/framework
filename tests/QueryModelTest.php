<?php

use \Bow\Database\Database;

class Pets extends \Bow\Database\Barry\Model
{
    /**
     * @var string
     */
    protected $table = "pets";

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     */
    protected $timestamps = false;
}

class QueryModelTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConnection()
    {
        return Database::getInstance();
    }

    /**
     * @param Database $db
     * @depends testGetConnection
     */
    public function testInstanceOfModel(Bow\Database\Database $db)
    {
        $pet = new Pets();

        $pet = $pet->first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testInstanceOfModel2(Bow\Database\Database $db)
    {
        $pet = new Pets();

        $pet = $pet->take(1)->get()->first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testInstanceCollectionOf(Bow\Database\Database $db)
    {
        $pets = Pets::all();

        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testChainSelectOf(Bow\Database\Database $db)
    {
        $pets = Pets::where('id', 1)->select(['name'])->get();

        $this->assertInstanceOf(Bow\Support\Collection::class, $pets);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testCountOf(Bow\Database\Database $db)
    {
        $pets = Pets::count();

        $this->assertEquals(is_int($pets), true);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::count();

        $a = Pets::all()->count();

        $this->assertEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     */
    public function testNotCountSelectCountOf(Bow\Database\Database $db)
    {
        $b = Pets::where('id', 1)->count();

        $a = Pets::all()->count();

        $this->assertNotEquals($a, $b);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testSaveOf(Bow\Database\Database $db)
    {
        $pet = Pets::first();

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testInsert(Bow\Database\Database $db)
    {
        $pet = Pets::create([
            'name' => 'Couli',
            'id' => 1
        ]);

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testFind(Bow\Database\Database $db)
    {
        $pet = Pets::find(1);

        $this->assertInstanceOf(Pets::class, $pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testFindEmpty(Bow\Database\Database $db)
    {
        $pet = Pets::find(100);

        $this->assertNull($pet);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testFindBy(Bow\Database\Database $db)
    {
        $pet = Pets::findBy('id', 1);

        $this->assertNotEquals($pet->count(), 0);
    }

    /**
     * @depends testGetConnection
     * @param Database $db
     */
    public function testFindByEmpty(Bow\Database\Database $db)
    {
        $pet = Pets::findBy('id', 100);

        $this->assertEquals($pet->count(), 0);
    }
}
