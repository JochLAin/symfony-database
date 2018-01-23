<?php

namespace Jochlain\Database\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * @namespace Jochlain\Database\ORM
 * @class Repository
 * @extends Doctrine\ORM\EntityRepository
 * 
 * @author Jocelyn Faihy <jocelyn@faihy.fr>
 */
class Repository extends EntityRepository
{
    const OPERATORS = ['=', '>', '<', '>=', '<=', '<>', '!='];
    const SEPARATOR = '__';

    const RESULT_SHAPE_STORE = 'store';
    const RESULT_SHAPE_COLLECTION = 'collection';
    const RESULT_SHAPE_OBJECT = 'object';
    const RESULT_SHAPE_BUILDER = 'builder';
    const RESULT_SHAPE_SQL = 'sql';
    const RESULT_SHAPE_RESULT = 'result';
    const RESULT_SHAPE_ARRAY = 'array';
    const RESULT_SHAPE_ONE = 'one';
    const RESULT_SHAPE_QUERY = 'query';
    const RESULT_SHAPE_SCALAR = 'scalar';

    /**
     * Return number of entities
     *
     * @param Doctrine\ORM\QueryBuilder = null
     * @return integer
     */
    public function count(array $constraints = [], QueryBuilder $qb = null)
    {
        $qb = !is_null($qb) ? $qb : $this->createQueryBuilder('e');
        if (count($constraints)) $qb = $this->_by($qb, $constraints);
        return $this->arrange($this->_count($qb), Repository::RESULT_SHAPE_SCALAR);
    }

    /**
     * Result basic results for basic constraints request
     * Examples constraints : [ 'statut' => 4, 'translations' => [ 'value' => '%foo%' ] ]
     *
     * @param array $constraints
     * @param Doctrine\ORM\QueryBuilder $qb = null
     * @return array
     */
    public function filter(array $constraints = [], string $shape = null, QueryBuilder $qb = null)
    {
        $qb = !is_null($qb) ? $qb : $this->createQueryBuilder('e');
        return $this->arrange($this->_by($qb, $constraints), $shape);
    }

    public function fetch(array $columns = null, array $constraints = [], string $shape = null, int $offset = null, int $limit = null, array $sorts = [], QueryBuilder $qb = null)
    {
        $qb = !is_null($qb) ? $qb : $this->createQueryBuilder('e');
        $qb->resetDQLPart('select');
        $qb = $this->_query($qb, $columns ?: ['id']);
        if (count($constraints)) $qb = $this->_by($qb, $constraints);
        if (count($sorts))       $qb = $this->_sort($qb, $sorts);
        if ($offset || $limit)   $qb = $this->_paginate($qb, $offset, $limit);

        if ($shape == Repository::RESULT_SHAPE_STORE) {
            if (count($columns) == 1) {
                return array_map(function ($entity) use ($columns) { 
                    return $entity[$columns[0]];
                }, $this->arrange($qb, Repository::RESULT_SHAPE_ARRAY));
            } else return $this->arrange($qb, Repository::RESULT_SHAPE_ARRAY);
        } else if (!$shape || $shape == Repository::RESULT_SHAPE_COLLECTION) {
            return $this->parse($qb, $this->getClassMetadata(), $this->getEntityManager());
        } else if (!$shape || $shape == Repository::RESULT_SHAPE_OBJECT) {
            return $this->parse($qb, $this->getClassMetadata(), $this->getEntityManager())[0];
        }
        return $this->arrange($qb, $shape);
    }

    /**
     * Improve QueryBuilder with basic count request
     *
     * @param Doctrine\ORM\QueryBuilder
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function _count(QueryBuilder $qb)
    {
        return $qb->select('COUNT(e.id)');
    }

    /**
     * Improve QueryBuilder with basic paginate request
     *
     * @param Doctrine\ORM\QueryBuilder
     * @param integer $offset
     * @param integer $limit
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function _paginate(QueryBuilder $qb, int $offset, int $limit)
    {
        return $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    /**
     * Improve QueryBuilder with basic queries request
     * Examples queries : [ 'statut', 'translations' => [ 'value' ] ]
     *
     * @param Doctrine\ORM\QueryBuilder
     * @param array $queries
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function _query(QueryBuilder $qb, array $columns)
    {
        return $this->inquire($qb, $columns, $this->getClassMetadata(), 'e', $this->getEntityManager());
    }

    /**
     * Improve QueryBuilder with basic constraints request
     * Examples constraints : [ 'statut' => 4, 'translations' => [ 'value' => '%foo%' ] ]
     *
     * @param Doctrine\ORM\QueryBuilder
     * @param array $constraints
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function _by(QueryBuilder $qb, array $constraints = [])
    {
        return $this->restraint($qb, $constraints, $this->getClassMetadata(), 'e', $this->getEntityManager());
    }

    /**
     * Improve QueryBuilder for basic sort request
     * Examples sorts : [ 'created_at' => 'DESC', 'id' => 'ASC' ]
     *
     * @param Doctrine\ORM\QueryBuilder
     * @param array $sorts
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function _sort(QueryBuilder $qb, array $sorts = [])
    {
        foreach ($sorts as $field => $direction) {
            $qb->addOrderBy('e.'.$field, $direction);
        }
        return $qb;
    }

    private function restraint(QueryBuilder $qb, array $constraints, ClassMetadata $metadata, string $entity, EntityManagerInterface $em)
    {
        foreach ($constraints as $key => $value) {
            $name = $this->join('constraint', $entity, $key);
            if ($metadata->hasField($key)) {
                $mapping = $metadata->getFieldMapping($key);
                if ($value === null) {
                    $this->operator($qb, $entity.'.'.$key.' #operator #value', 'NULL', 'IS');
                } else if ($mapping['type'] == 'json_array') {
                    $this->operator($qb, sprintf("ARRAY_CONTAINS(%s.%s, '#value') #operator false", $entity, $key), $value, '!=');
                } else if (in_array($mapping['type'], ['string', 'text'])) {
                    $this->operator($qb, $entity.'.'.$key.' #operator #value', $value, 'LIKE', $name);
                } else if (in_array($mapping['type'], ['integer', 'smallint', 'bigint', 'decimal', 'float'])) {
                    $operator = '=';
                    if (is_array($value) && in_array($value[0], Repository::OPERATORS)) {
                        $operator = $value[0];
                        $value = $value[1];
                    }
                    $this->operator($qb, $entity.'.'.$key.' #operator #value', $value, $operator, $name);
                } else if ($mapping['type'] == 'boolean') {
                    $this->operator($qb, $entity.'.'.$key.' #operator #value', $value ? 'TRUE' : 'FALSE', '=');
                }
            } else if ($metadata->hasAssociation($key)) {
                if (!$value) {
                    $this->operator($qb, $entity.'.'.$key.' #operator #value', 'NULL', 'IS');
                } else if (!is_array($value) || array_keys($value) === range(0, count($value) - 1)) {
                    $qb->leftJoin($entity.'.'.$key, $name);
                    $this->operator($qb, $name.'.id #operator #value', $value, '=');
                } else {
                    $qb->leftJoin($entity.'.'.$key, $name);
                    $metadata = $em->getClassMetadata($metadata->associationMappings[$key]['targetEntity']);
                    $qb = $this->restraint($qb, $value, $metadata, $name, $em);
                }
            }
        }
        return $qb;
    }

    private function inquire(QueryBuilder $qb, array $columns, ClassMetadata $metadata, string $entity, EntityManagerInterface $em, string $prefix = null) 
    {
        foreach ($columns as $key => $column) {
            if (is_int($key)) $key = $column;
            $alias = $prefix ? $this->join($prefix, $key) : $key;
            if ($metadata->hasField($key)) {
                $qb->addSelect($entity.'.'.$key.' AS '.$alias);
            } else if ($metadata->hasAssociation($key)) {
                $name = $this->join('column', $entity, $key);
                $qb->leftJoin($entity.'.'.$key, $name);
                if ($key == $column) $column = ['id'];
                else if (is_string($column)) $column = [$column];
                $_metadata = $em->getClassMetadata($metadata->associationMappings[$key]['targetEntity']);
                $qb = $this->inquire($qb, $column, $_metadata, $name, $em, $alias);
            }
        }
        return $qb;
    }

    private function operator(QueryBuilder $qb, string $condition, $values, string $operator = '=', string $alias = null) {
        if (is_array($values)) {
            foreach ($values as $index => $value) {
                if ($alias) {
                    $key = $this->join($alias, $index);
                    $qb->orWhere($this->replace($condition, $operator, ':'.$key))
                       ->setParameter($key, $value);
                } else $qb->orWhere($this->replace($condition, $operator, $value));
            }
        } else {
            if ($alias) {
                $qb->andWhere($this->replace($condition, $operator, ':'.$alias))
                   ->setParameter($alias, $values);
            } else $qb->andWhere($this->replace($condition, $operator, $values));
        }
    }

    private function replace(string $condition, string $operator, $value) {
        $condition = str_replace('#operator', $operator, $condition);
        $condition = str_replace('#value', $value, $condition);
        return $condition;
    }

    private function parse(QueryBuilder $qb, ClassMetadata $metadata, EntityManagerInterface $em) {
        $results = $this->arrange($qb, Repository::RESULT_SHAPE_ARRAY);
        $response = [];
        foreach ($results as $index => $result) {
            foreach ($result as $key => $value) {
                $this->assign($response, $value, $index, ...$this->split($key));
            }
        }

        $collection = [];
        $element = null;
        foreach ($response as $item) {
            $current = [];
            foreach ($item as $key => $value) {
                $field = $this->to_camel_case($key);
                if ($metadata->hasField($field)) {
                    $current[$key] = $value;
                    if ($element && $element[$key] != $value) {
                        $collection[] = $element;
                        $element = null;
                    }
                } else if ($metadata->hasAssociation($field)) {
                    if (count($value) == 1) $value = array_shift($value);
                    $mapping = $metadata->getAssociationMapping($field);
                    switch ($mapping['type']) {
                        case ClassMetadata::ONE_TO_ONE:
                        case ClassMetadata::MANY_TO_ONE:
                            $current[$key] = $value;
                            if ($element && $element[$key] != $value) {
                                $collection[] = $element;
                                $element = null;
                            }
                            break;
                        case ClassMetadata::ONE_TO_MANY:
                        case ClassMetadata::MANY_TO_MANY:
                            if (!isset($current[$key])) $current[$key] = [];
                            if ($value) $current[$key][] = $value;
                            if ($element && $value) $element[$key][] = $value;
                            break;
                    }
                }
            }
            if (!$element) $element = $current;
        }
        if ($element) $collection[] = $element;
        return $collection;
    }

    private function join(...$pathes) {
        return implode(Repository::SEPARATOR, $pathes);
    }
    private function split($path) {
        return explode(Repository::SEPARATOR, $path);
    }

    public function arrange(QueryBuilder $qb, string $shape = null) {
        switch ($shape) {
            case Repository::RESULT_SHAPE_ARRAY:
            case Repository::RESULT_SHAPE_COLLECTION:
            case Repository::RESULT_SHAPE_OBJECT:
            case Repository::RESULT_SHAPE_STORE:
                return $qb->getQuery()->getArrayResult();
            case Repository::RESULT_SHAPE_BUILDER:
                return $qb;
            case Repository::RESULT_SHAPE_ONE:
                return $qb->getQuery()->getOneOrNullResult();
            case Repository::RESULT_SHAPE_QUERY:
                return $qb->getQuery();
            case Repository::RESULT_SHAPE_SCALAR:
                return $qb->getQuery()->getSingleScalarResult();
            case Repository::RESULT_SHAPE_SQL:
                return $qb->getQuery()->getSql();
            case Repository::RESULT_SHAPE_RESULT:
            default:
                return $qb->getQuery()->getResult();
        }
    }

    private function assign(&$collection, $value, ...$keys) {
        $indice = $this->toSnakeCase(array_shift($keys));
        if (!count($keys)) {
            $collection[$indice] = $value;
        } else {
            if (!isset($collection[$indice])) $collection[$indice] = [];
            $this->assign($collection[$indice], $value, ...$keys);
        }
    }
    private function toSnakeCase(string $key) { 
        return ltrim(ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $key))), '_'); 
    }
    private function to_camel_case(string $key) { 
        return lcfirst(implode('', explode('_', ucwords($key, '_')))); 
    }
}
