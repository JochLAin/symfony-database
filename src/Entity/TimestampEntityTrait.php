<?php

namespace Jochlain\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @namespace Jochlain\Database\Entity
 * @class TimestampEntityTrait
 * 
 * @author Jocelyn Faihy <jocelyn@faihy.fr>
 */
trait TimestampEntityTrait
{
    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;
    /** @ORM\PrePersist */
    public function autoCreatedAt() { $this->createdAt = new \DateTime; }

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;
    /** @ORM\PrePersist @ORM\PreUpdate */
    public function autoUpdatedAt() { $this->updatedAt = new \DateTime; }
}
