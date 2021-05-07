<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class NewsCategory
 * @package App\Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="news_category",indexes={@ORM\Index(name="news_category_english_name_idx", columns={"english_name"})})
 */
class NewsCategory
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $englishName;

    /**
     * @var ArrayCollection|News[]
     * @ORM\OneToMany(targetEntity="App\Entity\News", mappedBy="category", cascade={"all"})
     */
    protected $news;

    public function __construct()
    {
        $this->news = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEnglishName(): string
    {
        return $this->englishName;
    }

    /**
     * @param string $englishName
     */
    public function setEnglishName(string $englishName): void
    {
        $this->englishName = $englishName;
    }

    /**
     * @return News[]|ArrayCollection
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @param News[]|ArrayCollection $news
     */
    public function setNews($news): void
    {
        $this->news = $news;
    }

    /**
     * @param News $news
     */
    public function addNews(News $news): void
    {
        if (!$this->news->contains($news)){
            $this->news->add($news);
        }
    }

    /**
     * @param News $news
     */
    public function removeNews(News $news): void
    {
        if ($this->news->contains($news)){
            $this->news->removeElement($news);
        }
    }

    public function __toString()
    {
        return $this->getName();
    }
}