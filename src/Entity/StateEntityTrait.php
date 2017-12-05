<?php 

namespace JochLAin\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

trait StateEntityTrait
{
    /**
     * @ORM\Column(type="integer")
     */
    protected $state = 20;
    public function isInactive() { return $this->state <= getenv('ENTITY_STATE_INACTIVE'); }
    public function isActive() { return $this->state >= getenv('ENTITY_STATE_ACTIVE'); }

    public function isDeleted() { return $this->state == getenv('ENTITY_STATE_DELETED'); }
    public function isArchived() { return $this->state == getenv('ENTITY_STATE_ARCHIVED'); }
    public function isPending() { return $this->state == getenv('ENTITY_STATE_PENDING'); }
    public function isRead() { return $this->state == getenv('ENTITY_STATE_READ'); }
    public function isRefused() { return $this->state == getenv('ENTITY_STATE_REFUSED'); }
    public function isAccepted() { return $this->state == getenv('ENTITY_STATE_ACCEPTED'); }
}