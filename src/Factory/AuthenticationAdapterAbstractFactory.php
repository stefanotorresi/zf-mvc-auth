<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\Authentication;

class AuthenticationAdapterAbstractFactory implements AbstractFactoryInterface
{
    const INTERNAL_FACTORIES = [
        Authentication\HttpAdapter::class,
        Authentication\OAuth2Adapter::class,
        Authentication\CompositeAdapter::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $adapterName = substr($name, strrpos($name, '-') + 1);
        $config      = $container->get('config');
        $adapterSpec = $config['zf-mvc-auth']['authentication']['adapters'][$adapterName];

        if (! isset($adapterSpec['adapter']) || ! is_string($adapterSpec['adapter'])) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create service "%s"; no adapter configuration found in zf-mvc-auth configuration',
                $name
            ));
        }

        switch ($adapterSpec['adapter']) {
            case Authentication\HttpAdapter::class:
                return AuthenticationHttpAdapterFactory::factory($adapterName, $adapterSpec, $container);
            case Authentication\OAuth2Adapter::class:
                return AuthenticationOAuth2AdapterFactory::factory($adapterName, $adapterSpec, $container);
            case Authentication\CompositeAdapter::class:
                return AuthenticationCompositeAdapterFactory::factory($adapterName, $adapterSpec, $container);
            default:
                return $container->get($adapterSpec['adapter']);
        }
    }

    public function canCreate(ContainerInterface $container, $name)
    {
        $config = $container->get('config');

        $hasMatches = preg_match('/^zf-mvc-auth-authentication-adapters-(?P<name>\w+)$/', $name, $matches);

        if (! $hasMatches) {
            return false;
        }

        $adapterName = $matches['name'];

        if (! isset($config['zf-mvc-auth']['authentication']['adapters'][$adapterName]['adapter'])) {
            return false;
        }

        $adapter = $config['zf-mvc-auth']['authentication']['adapters'][$adapterName]['adapter'];

        if (! is_string($adapter)) {
            return false;
        }

        if ($container->has($adapter) && ! $container->get($adapter) instanceof Authentication\AdapterInterface) {
            return false;
        }

        if (! $container->has($adapter) && ! in_array($adapter, self::INTERNAL_FACTORIES, true)) {
            return false;
        }

        return true;
    }

    /**
     * zend-servicemanager v2 support.
     *
     * {@inheritdoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->canCreate($serviceLocator, $requestedName);
    }

    /**
     * zend-servicemanager v2 support.
     *
     * {@inheritdoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this($serviceLocator, $requestedName);
    }
}
