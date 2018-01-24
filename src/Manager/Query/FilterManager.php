<?php 

namespace Jochlain\Database\Manager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Jochlain\Database\ORM\Repository;

/**
 * @author Jocelyn Faihy <jfaihy@gmail.com>
 */
class FilterManager 
{
    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function filter(string $classname, array $constraints = null) {
        return FilterManager::filters($this->em, $classname, $constraints);
    }

    public static function filters(EntityManagerInterface $em, string $classname, array $constraints = []) {
        $configuration = $em->getConfiguration();
        $default = $configuration->getDefaultRepositoryClassName();
        $configuration->setDefaultRepositoryClassName(Repository::class);

        $repository = $em->getRepository($classname);
        $entities = $repository->filter($constraints);

        $configuration->setDefaultRepositoryClassName($default);
        return $entities;
    }
}