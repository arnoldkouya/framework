<?php

use Bow\Translate\Translator;

class TranslationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass()
    {
        Translator::configure('fr', __DIR__.'/config/lang');
    }

    public function testWelcome()
    {
        $this->assertEquals(Translator::translate('welcome.message'), 'bow framework');
    }

    public function testUserName()
    {
        $this->assertEquals(Translator::translate('welcome.user.name'), 'Dakia Franck');
    }

    public function testPlurial()
    {
        $this->assertEquals(Translator::plurial('welcome.plurial'), 'users');
    }

    public function testSingle()
    {
        $this->assertEquals(Translator::single('welcome.plurial'), 'user');
    }

    public function testBindData()
    {
        $this->assertEquals(Translator::single('welcome.hello', ['name' => 'papac']), 'hello papac');
    }
}
