<?php 

namespace Jochlain\Database\Manager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Jochlain\Database\ORM\Repository;

/**
 * @author Jocelyn Faihy <jfaihy@gmail.com>
 */
class FetchManager 
{
    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function fetch(string $classname, array $constraints = null) {
        return FetchManager::fetches($this->em, $classname, $constraints);
    }

    public static function fetches(EntityManagerInterface $em, string $classname, array $columns = null, array $constraints = [], int $offset = null, int $limit = null, array $sorts = []) {
        $configuration = $em->getConfiguration();
        $default = $configuration->getDefaultRepositoryClassName();
        $configuration->setDefaultRepositoryClassName(Repository::class);

        $repository = $em->getRepository($classname);
        $entities = $repository->fetch($columns, $constraints, null, $offset, $limit, $sorts);

        $configuration->setDefaultRepositoryClassName($default);
        return $entities;
    }
}