<?php 

namespace Jochlain\Database\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Jochlain\Database\Manager\Query\CountManager;
use Jochlain\Database\Manager\Query\FetchManager;
use Jochlain\Database\Manager\Query\FilterManager;

/**
 * @author Jocelyn Faihy <jfaihy@gmail.com>
 */
class QueryManager 
{
    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function count(string $classname, array $contraints = []) {
        return CountManager::counts($this->em, $classname, $constraints);
    }

    public function filter(string $classname, array $contraints = []) {
        return FilterManager::filters($this->em, $classname, $constraints);
    }

    public function fetch(string $classname, array $columns = null, array $constraints = [], int $offset = null, int $limit = null, array $sorts = []) {
        return FetchManager::fetches($this->em, $classname, $columns, $constraints, $offset, $limit, $sorts);
    }
}