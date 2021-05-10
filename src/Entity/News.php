<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use phpQuery;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class News
 * @package App\Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\NewsRepository")
 * @ORM\Table(name="news",indexes={
 *     @ORM\Index(name="news_pub_date_idx", columns={"pub_date"}),
 *     @ORM\Index(name="news_guid_idx", columns={"guid"})
 * })
 */
class News
{
    /** @var string */
    public const RSS_HOST = 'https://news.yandex.ru/';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected string $guid;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $title;

    /**
     * @var string
     * @ORM\Column(type="string", length=512)
     */
    protected string $description;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected int $pubDate;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $link;

    /**
     * @var NewsCategory
     * @ORM\ManyToOne(targetEntity="App\Entity\NewsCategory", inversedBy="news")
     */
    protected $category;

    /**
     * @param SimpleXMLElement $data
     * @param NewsCategory $category
     */
    public function __construct(SimpleXMLElement $data, NewsCategory $category)
    {
        $this->setCategory($category);
        $this->setGuid((string)$data->guid);
        $this->setLink((string)$data->link);
        $this->setTitle((string)$data->title);
        $this->setDescription((string)$data->description);
        $this->setPubDate(strtotime((string)$data->pubDate));
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
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return html_entity_decode($this->description);
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getPubDate(): int
    {
        return $this->pubDate;
    }

    /**
     * @param int $pubDate
     */
    public function setPubDate(int $pubDate): void
    {
        $this->pubDate = $pubDate;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getOriginalLink()
    {
        $html = file_get_contents($this->getLink());
        if ($html === false) {
            throw new RuntimeException('Яндекс.Новости недоступны');
        }

        try {
            $page = phpQuery::newDocument($html);
            $link = $page->find('.mg-story__meta')->attr('href');

            return !empty($link) ? $link : $this->getLink();
        } catch (Exception $e) {
            throw new RuntimeException('Ошибка парсинга HTML. ' . $e->getMessage());
        }
    }

    /**
     * @return NewsCategory
     */
    public function getCategory(): NewsCategory
    {
        return $this->category;
    }

    /**
     * @param NewsCategory $category
     */
    public function setCategory(NewsCategory $category): void
    {
        $this->category = $category;
    }
}
