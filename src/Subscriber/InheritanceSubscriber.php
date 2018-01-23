<?php 

namespace Jochlain\Database\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Jochlain\Database\MagicEntityTrait;

/**
 * @namespace Jochlain\Database\Subscriber
 * @class InheritanceSubscriber
 * @implements Doctrine\Common\EventSubscriber
 *
 * @author Jocelyn Faihy <jocelyn@faihy.fr>
 */
class InheritanceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents() {
        return [Events::loadClassMetadata];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args) {
        $metadata = $args->getClassMetadata();
        if (!in_array(MagicEntityTrait::class, class_uses($metadata->getReflectionClass()->getName()))) return;

        $em = $args->getEntityManager();
        $tree = $this->getInheritanceTree($em);

        if (!isset($tree[$metadata->getName()])) return;

        if (!$metadata->inheritanceType || $metadata->inheritanceType == ClassMetadata::INHERITANCE_TYPE_NONE) {
            $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);
        }

        if (!$metadata->discriminatorColumn) {
            $keys = ['discriminator', 'discr', 'dtype', 'discrtype', 'discriminatortype'];
            $fields = $metadata->getFieldNames();
            do $key = array_shift($keys); while (count($keys) && in_array($key, $fields));
            $metadata->setDiscriminatorColumn([ 'name' => $key, 'type' => 'string' ]);
        }

        $metadata->setDiscriminatorMap($tree[$metadata->getName()]);
    }

    private function getInheritanceTree(EntityManagerInterface $em) {
        // $cache = $em->getConfiguration()->getMetadataCacheImpl();
        // if ($tree = $cache->fetch('$INHERITANCE_TREE')) return $tree;

        $classnames = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
        $map = [];

        foreach ($classnames as $parent) {
            foreach ($classnames as $child) {
                if (is_subclass_of($child, $parent)) {
                    if (!isset($map[$parent])) $map[$parent] = [];
                    $map[$parent][] = $child;
                }
            }
        }

        $duplicates = [];
        foreach ($map as $parent => $value) {
            foreach ($map as $children) {
                if (in_array($parent, $children)) {
                    $duplicates[] = $parent;
                }
            }
        }
        foreach ($duplicates as $key) unset($map[$key]);

        $tree = [];
        foreach ($map as $parent => $children) {
            $tree[$parent] = [];
            $children[] = $parent;
            foreach ($children as $child) {
                $tree[$parent][$this->getKey($child)] = $child;
            }
        }

        // $cache->save('$INHERITANCE_TREE', $tree);
        return $tree;
    }

    private function getKey($classname) {
        $reflection = new \ReflectionClass($classname);
        $namespace = $reflection->getNamespaceName();
        $key = str_replace('\\', '_', substr($namespace, 0, strpos($namespace, 'Bundle')));
        if (strpos($namespace, '\\Entity\\') !== FALSE) {
            $key .= '_'.str_replace('\\', '_', substr($namespace, strpos($namespace, '\\Entity\\') + strlen('\\Entity\\')));
        }
        $key .= '_'.$reflection->getShortName();
        return strtolower($key);
    }
}