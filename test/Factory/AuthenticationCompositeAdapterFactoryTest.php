<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Factory\AuthenticationCompositeAdapterFactory;

class AuthenticationCompositeAdapterFactoryTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|MockObject
     */
    protected $serviceLocator;

    public function setUp()
    {
        $this->serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
    }

    public function invalidConfiguration()
    {
        return [
            'empty'  => [[]],
            'null'   => [['adapters' => null]],
            'bool'   => [['adapters' => true]],
            'int'    => [['adapters' => 1]],
            'float'  => [['adapters' => 1.1]],
            'string' => [['adapters' => 'options']],
            'object' => [['adapters' => (object) ['storage']]],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config)
    {
        $this->setExpectedException(
            'Zend\ServiceManager\Exception\ServiceNotCreatedException',
            'No adapters configured'
        );
        AuthenticationCompositeAdapterFactory::factory('foo', $config, $this->serviceLocator);
    }

    public function testCreatesInstanceFromValidConfiguration()
    {
        $config = [
            'adapters' => ['foo', 'bar'],
        ];

        $fooAdapter = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $barAdapter = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $fooAdapter
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue(['foo']))
        ;
        $barAdapter
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue(['bar']))
        ;

        $this->serviceLocator->expects($this->any())
            ->method('get')
            ->with($this->logicalOr(
                'zf-mvc-auth-authentication-adapters-foo',
                'zf-mvc-auth-authentication-adapters-bar'
            ))
            ->will($this->returnCallback(function ($name) use ($fooAdapter, $barAdapter) {
                switch ($name) {
                    case 'zf-mvc-auth-authentication-adapters-foo':
                        return $fooAdapter;
                    case 'zf-mvc-auth-authentication-adapters-bar':
                        return $barAdapter;
                }
            }));

        $adapter = AuthenticationCompositeAdapterFactory::factory('foobar', $config, $this->serviceLocator);
        $this->assertInstanceOf('ZF\MvcAuth\Authentication\CompositeAdapter', $adapter);
        $this->assertEquals(['foo', 'bar', 'foobar'], $adapter->provides());
    }
}
