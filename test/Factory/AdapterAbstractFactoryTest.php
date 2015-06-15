<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Factory\AdapterAbstractFactory;

class AdapterAbstractFactoryTest extends TestCase
{
    /**
     * @var AdapterAbstractFactory
     */
    protected $factory;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function setUp()
    {
        $this->factory        = new AdapterAbstractFactory();
        $this->serviceManager = new ServiceManager();

        $config = [
            'zf-oauth2' => [
                'grant_types' => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ],
            'zf-mvc-auth' => [
                'authentication' => [
                    'adapters' => [
                        'foo' => [
                            'adapter' => 'ZF\MvcAuth\Authentication\HttpAdapter',
                            'options' => [
                                'accept_schemes' => ['basic'],
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ],
                        ],
                        'bar' => [
                            'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'adapter' => 'pdo',
                                'dsn' => 'sqlite::memory:',
                            ],
                        ],
                        'baz' => [
                            'adapter' => 'CUSTOM',
                        ],
                        'bat' => [
                            // intentionally empty
                        ],
                        'batman' => [
                            'adapter' => 'ZF\MvcAuth\Authentication\CompositeAdapter',
                            'adapters' => ['foo', 'bar'],
                        ],
                    ],
                ],
            ],
        ];

        $this->serviceManager->setService('config', $config);
        $this->serviceManager->setService(
            'authentication',
            $this->getMock('Zend\Authentication\AuthenticationService')
        );
        $this->serviceManager->setService('CUSTOM', $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface'));
        $this->serviceManager->addAbstractFactory($this->factory);
    }

    public function testCanCreateServiceWithName()
    {
        $this->assertTrue($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-foo'
        ));

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-bar'
        ));

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-baz'
        ));

        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-bat'
        ));

        $this->assertFalse($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'foo-bar-baz'
        ));

        $this->assertTrue($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-batman'
        ));
    }

    public function testCreateServiceWithName()
    {
        $fooAdapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-foo'
        );

        $this->assertInstanceOf('\ZF\MvcAuth\Authentication\HttpAdapter', $fooAdapter);

        $barAdapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-bar'
        );

        $this->assertInstanceOf('ZF\MvcAuth\Authentication\OAuth2Adapter', $barAdapter);

        $batmanAdapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-batman'
        );

        $this->assertInstanceOf('ZF\MvcAuth\Authentication\CompositeAdapter', $batmanAdapter);

        $bazAdapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-baz'
        );

        $this->assertSame($bazAdapter, $this->serviceManager->get('CUSTOM'));

        $this->assertFalse($this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-bat'
        ));
    }
}
