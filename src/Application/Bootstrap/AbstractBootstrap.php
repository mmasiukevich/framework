<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap;

use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Desperado\ServiceBus\Application\Bootstrap\Exceptions as BootstrapExceptions;
use Desperado\ServiceBus\DependencyInjection\ServiceBusExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection as SymfonyDependencyInjection;

/**
 * Base class initial initialization of the application
 */
abstract class AbstractBootstrap
{
    /**
     * Absolute path to the root of the application
     *
     * @var string
     */
    private $rootDirectoryPath;

    /**
     * Absolute path to the cache directory
     *
     * @var string
     */
    private $cacheDirectoryPath;

    /**
     * Service bus configuration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * Dependency injection container
     *
     * @var SymfonyDependencyInjection\Container
     */
    private $container;

    /**
     * Initializing components
     *
     * @param string $rootDirectoryPath   Absolute path to the root of the application
     * @param string $environmentFilePath Absolute path to the ".env" configuration file
     * @param string $cacheDirectoryPath  Absolute path to the cache directory
     *
     * @return self
     *
     * @throws \InvalidArgumentException
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     * @throws BootstrapExceptions\ServiceBusConfigurationException
     * @throws \Exception
     */
    public static function boot(
        string $rootDirectoryPath,
        string $cacheDirectoryPath,
        string $environmentFilePath
    ): self
    {
        $startTimer = \microtime(true);

        $self = new static($rootDirectoryPath, $cacheDirectoryPath);

        $self->configuration = Configuration::loadDotEnv($environmentFilePath);
        $self->configuration->validate();

        $self->container = $self->initializeContainer();

        $self->init();

        LoggerRegistry::getLogger('bootstrap')
            ->info(
                \sprintf('Application initialization time: %g', \microtime(true) - $startTimer)
            );

        return $self;
    }

    /**
     * Get dependency injection container
     *
     * @return SymfonyDependencyInjection\Container
     */
    final public function getContainer(): SymfonyDependencyInjection\Container
    {
        return $this->container;
    }

    /**
     * Get customer-configurable services
     *
     * @return BootstrapServicesDefinitions
     */
    abstract protected function getBootstrapServicesDefinitions(): BootstrapServicesDefinitions;

    /**
     * Get customer-configurable container parameters
     *
     * @return BootstrapContainerConfiguration
     */
    abstract protected function getBootstrapContainerConfiguration(): BootstrapContainerConfiguration;

    /**
     * Custom component initialization (container already compiled)
     *
     * @return void
     */
    protected function init(): void
    {

    }

    /**
     * @param string $rootDirectoryPath
     * @param string $cacheDirectoryPath
     *
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     */
    final protected function __construct(string $rootDirectoryPath, string $cacheDirectoryPath)
    {
        $this->cacheDirectoryPath = $this->prepareCacheDirectoryPath($cacheDirectoryPath);
        $this->rootDirectoryPath = $this->prepareRootDirectoryPath($rootDirectoryPath);

        configureAnnotationsLoader();
    }

    /**
     * Build dependency injection container
     *
     * @return ContainerInterface
     *
     * @throws BootstrapExceptions\ServiceBusConfigurationException
     */
    private function initializeContainer(): ContainerInterface
    {
        try
        {
            $builder = new ContainerBuilder($this->configuration, $this->rootDirectoryPath, $this->cacheDirectoryPath);

            /** If the container does not need to be re-created, just get it */
            if(
                true === $builder->isCacheActual() &&
                true === $this->configuration->getEnvironment()->isProduction()
            )
            {
                return $builder->getContainer();
            }

            /** Assemble a new container */

            $bootstrapContainerConfiguration = $this->getBootstrapContainerConfiguration();

            $builder->addExtension(new ServiceBusExtension($this->getBootstrapServicesDefinitions()));
            $builder->addExtensions($bootstrapContainerConfiguration->getExtensionsCollection());

            $builder->addCompilationPasses($bootstrapContainerConfiguration->getCompilerPassCollection());
            $builder->addContainerParameters($bootstrapContainerConfiguration->getCustomerParameters());

            return $builder->rebuild();

        }
        catch(\Throwable $throwable)
        {
            throw new BootstrapExceptions\ServiceBusConfigurationException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Prepare root directory path
     *
     * @param string $rootDirectoryPath
     *
     * @return string
     *
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     */
    private function prepareRootDirectoryPath(string $rootDirectoryPath): string
    {
        $rootDirectoryPath = \rtrim($rootDirectoryPath, '/');

        if('' === $rootDirectoryPath || false === \is_dir($rootDirectoryPath) || false === \is_readable($rootDirectoryPath))
        {
            throw new BootstrapExceptions\IncorrectRootDirectoryPathException($rootDirectoryPath);
        }

        return $rootDirectoryPath;
    }

    /**
     * Prepare cache directory
     *
     * @param string $cacheDirectoryPath
     *
     * @return string
     *
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     */
    private function prepareCacheDirectoryPath(string $cacheDirectoryPath): string
    {
        $cacheDirectory = new CacheDirectory($cacheDirectoryPath);
        $cacheDirectory->prepare();

        return (string) $cacheDirectory;
    }
}