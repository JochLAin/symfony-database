<?php 

namespace JochLAin\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

use JochLAin\Database\Entity\StateEntityTrait;

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