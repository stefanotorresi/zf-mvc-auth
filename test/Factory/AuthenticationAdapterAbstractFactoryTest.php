<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2018 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\MvcAuth\Factory;

use PHPUnit\Framework\TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceManager;
use ZF\MvcAuth\Authentication\AdapterInterface;
use ZF\MvcAuth\Authentication\CompositeAdapter;
use ZF\MvcAuth\Authentication\HttpAdapter;
use ZF\MvcAuth\Authentication\OAuth2Adapter;
use ZF\MvcAuth\Factory\AuthenticationAdapterAbstractFactory;

class AuthenticationAdapterAbstractFactoryTest extends TestCase
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
        $this->factory        = new AuthenticationAdapterAbstractFactory();
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
                            'adapter' => HttpAdapter::class,
                            'options' => [
                                'accept_schemes' => ['basic'],
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ],
                        ],
                        'bar' => [
                            'adapter' => OAuth2Adapter::class,
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
                            'adapter' => CompositeAdapter::class,
                            'adapters' => ['foo', 'bar'],
                        ],
                    ],
                ],
            ],
        ];

        $this->serviceManager->setService('config', $config);
        $this->serviceManager->setService(
            'authentication',
            $this->createMock(AuthenticationService::class)
        );
        $this->serviceManager->setService('CUSTOM', $this->createMock(AdapterInterface::class));
        $this->serviceManager->addAbstractFactory($this->factory);
    }

    public function servicesToTest()
    {
        return [
            'foo'         => ['zf-mvc-auth-authentication-adapters-foo', 'assertTrue'],
            'bar'         => ['zf-mvc-auth-authentication-adapters-bar', 'assertTrue'],
            'baz'         => ['zf-mvc-auth-authentication-adapters-baz', 'assertTrue'],
            'bat'         => ['zf-mvc-auth-authentication-adapters-bat', 'assertFalse'],
            'foo-bar-baz' => ['foo-bar-baz', 'assertFalse'],
            'batman'      => ['zf-mvc-auth-authentication-adapters-batman', 'assertTrue'],
        ];
    }

    /**
     * @dataProvider servicesToTest
     * @param string $serviceName
     * @param string $assertion
     */
    public function testCanCreateServiceWithName($serviceName, $assertion)
    {
        $this->$assertion($this->factory->canCreateServiceWithName(
            $this->serviceManager,
            '',
            $serviceName
        ));
    }

    public function servicesToFetch()
    {
        return [
            'foo'    => ['zf-mvc-auth-authentication-adapters-foo', HttpAdapter::class],
            'bar'    => ['zf-mvc-auth-authentication-adapters-bar', OAuth2Adapter::class],
            'batman' => ['zf-mvc-auth-authentication-adapters-batman', CompositeAdapter::class],
        ];
    }

    /**
     * @dataProvider servicesToFetch
     * @param string $serviceName
     * @param string $expectedType
     */
    public function testCreateServiceWithName($serviceName, $expectedType)
    {
        $adapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            $serviceName
        );

        $this->assertInstanceOf($expectedType, $adapter);
    }

    public function testCreateServiceCanProduceACustomAdapter()
    {
        $adapter = $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-baz'
        );

        $this->assertSame($adapter, $this->serviceManager->get('CUSTOM'));
    }

    public function testCreateServiceRaisesExceptionIfItCannotCreateAnAdapter()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->factory->createServiceWithName(
            $this->serviceManager,
            '',
            'zf-mvc-auth-authentication-adapters-bat'
        );
    }
}
