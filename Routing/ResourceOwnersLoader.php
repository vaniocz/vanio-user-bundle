<?php
namespace Vanio\UserBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ResourceOwnersLoader implements LoaderInterface
{
    /** @var string[] */
    private $resourceOwners;

    /**
     * @param string[] $resourceOwners
     */
    public function __construct(array $resourceOwners)
    {
        $this->resourceOwners = $resourceOwners;
    }

    /**
     * @param mixed $resource
     * @param string|null $type
     * @return RouteCollection
     */
    public function load($resource, $type = null): RouteCollection
    {
        $routes = new RouteCollection;

        foreach ($this->resourceOwners as $resourceOwner) {
            $routes->add("hwi_oauth_security_check.$resourceOwner", new Route("/$resourceOwner"));
        }

        return $routes;
    }

    /**
     * @param mixed $resource
     * @param string|null $type
     * @return bool
     */
    public function supports($resource, $type = null): bool
    {
        return $type === 'resource_owners';
    }

    public function getResolver(): void
    {}

    public function setResolver(LoaderResolverInterface $resolver): void
    {}
}
