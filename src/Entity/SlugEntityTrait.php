<?php 

namespace JochLAin\Database\Entity;

use App\Utils\Slug;

trait SlugEntityTrait
{
    /**
     * @ORM\Column(type="string")
     */
    private $slug;

    /** @ORM\PrePersist @ORM\PreUpdate */
    public function autoSlug() { 
        $content = '';
        if (isset($this->title)) $content = $this->title;
        else if (isset($this->name)) $content = $this->name;
        $this->slug = SlugEntityTrait::slugify($content); 
    }

    public static function slugify(string $text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text;
    }
}