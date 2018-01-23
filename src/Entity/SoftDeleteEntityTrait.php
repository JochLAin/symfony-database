<?php 

namespace Jochlain\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

use Jochlain\Database\Entity\StateEntityTrait;

/**
 * @namespace Jochlain\Database\Entity
 * @class SoftDeleteEntityTrait
 * 
 * @author Jocelyn Faihy <jocelyn@faihy.fr>
 */
trait SoftDeleteEntityTrait
{
    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    protected $deletedAt;
    /** @ORM\PreUpdate */
    public function autoDeletedAt() {
    	if (($this instanceof StateEntityTrait && $this->state != getenv('ENTITY_STATE_DELETED')) || $this->deletedAt) return;
        $this->deletedAt = new \DateTime;
        return $this;
    }
}