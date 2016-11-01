<?php
/**
 * Created by PhpStorm.
 * User: alfred
 * Date: 01.11.16
 * Time: 23:10
 */

namespace tests\ApiRator\Includes;

use ApiRator\Includes\Opts;

/**
 * Class OptsTest
 * @package tests\ApiRator\Includes
 */
class OptsTest extends \PHPUnit_Framework_TestCase
{
    private $magic_arg = 'test';
    private $magic_param = 'test';
    private $magic_param2 = 'test2';
    private $magic_value = 'value';

    public function testConstructor()
    {
        $opts = new Opts($this->magic_arg);
        $magic = $this->getMagic();

        $opts->$magic = $this->magic_value;

        $this->assertEquals($this->magic_value, $opts->$magic, 'Check magic arg');
    }

    public function testRequiredParam()
    {
        $opts = new Opts($this->magic_arg);

        $magic = $this->getMagic();
        $magic2 = $this->getMagic2();

        $opts->setRequiredParams([$this->magic_param]);
        try {
            $opts->checkRequiredParams();
            $error = '';
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }
        $this->assertContains($this->magic_param, $error, 'Check required param exception');

        $opts->$magic = $this->magic_value;
        try {
            $opts->checkRequiredParams();
            $error = '';
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }
        $this->assertEquals('', $error, 'Check required param empty');

        $opts->$magic2 = $this->magic_value;
        $opts->setRequiredParams($this->magic_param2);
        try {
            $opts->checkRequiredParams();
            $error = '';
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }
        $this->assertEquals('', $error, 'Check required param empty for set param as string');
    }

    public function testSetParameter()
    {
        $opts = new Opts($this->magic_arg);
        $magic = $this->getMagic();
        $params = [$this->magic_value];

        $opts->setParameter($this->magic_param, $this->magic_value);
        $this->assertEquals($this->magic_value, $opts->$magic, 'Check set param as string');

        $opts->setParameter($this->magic_param, $params);
        $this->assertContains($this->magic_value, $opts->$magic, 'Check set param as array');

        $opts->setParameter($this->magic_param, ['value' => $this->magic_value]);
        $this->assertEquals($this->magic_value, $opts->$magic, 'Check set param as array value');

        $opts->setParameter($this->magic_param2, ['required' => true]);
        try {
            $opts->checkRequiredParams();
            $error = '';
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }
        $this->assertContains($this->magic_param2, $error, 'Check set param as array required');
    }

    public function testSetParameters()
    {
        $opts = new Opts($this->magic_arg);
        $magic = $this->getMagic();

        $opts->setParameters([
            'value' => $this->magic_value
        ]);
        $this->assertNull($opts->$magic, 'Check set params as array wrong');

        $opts->setParameters([
            'value' => $this->magic_value
        ]);
        $this->assertNull($opts->$magic, 'Check set params as string wrong');

        $opts->setParameters([
            $this->magic_param => [
                'value' => $this->magic_value
            ]
        ]);
        $this->assertEquals($this->magic_value, $opts->$magic, 'Check set params as array value');

        $opts->setParameters([
            $this->magic_param2 => [
                'required' => true,
            ]
        ]);
        try {
            $opts->checkRequiredParams();
            $error = '';
        } catch (\Exception $ex) {
            $error = $ex->getMessage();
        }
        $this->assertContains($this->magic_param2, $error, 'Check set params as array required');
    }

    private function getMagic()
    {
        return $this->magic_arg . '_' . $this->magic_param;
    }

    private function getMagic2()
    {
        return $this->magic_arg . '_' . $this->magic_param2;
    }
}
