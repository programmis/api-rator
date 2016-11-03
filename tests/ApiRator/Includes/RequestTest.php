<?php
/**
 * Created by PhpStorm.
 * User: alfred
 * Date: 02.11.16
 * Time: 19:14
 */

namespace tests\ApiRator\Includes;

use ApiRator\Includes\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testExecApiOnVk()
    {
        $answer = null;
        $stub = $this->getMockForAbstractClass('ApiRator\Includes\Request', ['arg']);
        $stub->expects($this->any())
            ->method('getResultApiUrl')
            ->will($this->returnCallback(function () use ($stub) {
                /** @var Request $stub */
                return 'https://api.vk.com/method/'
                . $stub->getMethod()
                . '?v=' . $stub->getApiVersion();
            }));
        $stub->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('users.get'));
        $stub->expects($this->any())
            ->method('getApiVersion')
            ->will($this->returnValue('5.60'));
        $stub->expects($this->any())
            ->method('answerProcessing')
            ->will($this->returnCallback(function ($argument) use (&$answer) {
                $answer = json_decode($argument);

                return true;
            }));

        /** @var Request $stub */
        $stub->arg_user_ids = '1';
        $result = $stub->execApi();

        $this->assertTrue($result, 'Check answer');

        $this->assertTrue(isset(
            $answer->response,
            $answer->response[0],
            $answer->response[0]->id
        ), 'Check answer params');

        $this->assertContains('"response"', $stub->getOriginalAnswer(), 'Check contain "response" in original answer');
        $this->assertContains('"id":1', $stub->getOriginalAnswer(), 'Check contain "id":1 in original answer');
    }
}
