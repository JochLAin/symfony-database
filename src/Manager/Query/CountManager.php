<?php 

namespace Jochlain\Database\Manager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Jochlain\Database\ORM\Repository;

/**
 * @author Jocelyn Faihy <jfaihy@gmail.com>
 */
class CountManager 
{
    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function count(string $classname, array $constraints = null) {
        return CountManager::counts($this->em, $classname, $constraints);
    }

    public static function counts(EntityManagerInterface $em, string $classname, array $constraints = []) {
        $configuration = $em->getConfiguration();
        $default = $configuration->getDefaultRepositoryClassName();
        $configuration->setDefaultRepositoryClassName(Repository::class);

        $repository = $em->getRepository($classname);
        $entities = $repository->count($constraints);

        $configuration->setDefaultRepositoryClassName($default);
        return $entities;
    }
}