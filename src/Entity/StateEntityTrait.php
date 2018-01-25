<?php 

namespace Jochlain\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @namespace Jochlain\Database\Entity
 * @class StateEntityTrait
 * 
 * @author Jocelyn Faihy <jocelyn@faihy.fr>
 */
trait StateEntityTrait
{
    /** @ORM\Column(type="integer") */
    protected $state = 20;

    public function isInactive() { return $this->state <= getenv('ENTITY_STATE_INACTIVE') ?: 10; }
    public function isActive() { return $this->state >= getenv('ENTITY_STATE_ACTIVE') ?: 30; }

    public function isDeleted() { return $this->state == getenv('ENTITY_STATE_DELETED') ?: 1; }
    public function isArchived() { return $this->state == getenv('ENTITY_STATE_ARCHIVED') ?: 15; }
    public function isPending() { return $this->state == getenv('ENTITY_STATE_PENDING') ?: 20; }
    public function isRead() { return $this->state == getenv('ENTITY_STATE_READ') ?: 31; }
    public function isRefused() { return $this->state == getenv('ENTITY_STATE_REFUSED') ?: 50; }
    public function isAccepted() { return $this->state == getenv('ENTITY_STATE_ACCEPTED') ?: 51; }
}