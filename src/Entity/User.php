<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 * @package App\Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="usr")
 * @UniqueEntity("login", message="Данный пользователь уже зарегистрирован")
 */
class User implements UserInterface
{
    /** @var string */
    private const ROLE_USER = 'ROLE_USER';

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
     *
     * @ORM\Column(name="login")
     * @Assert\NotBlank(message="Необходимо заполнить логин")
     */
    protected string $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string")
     * @Assert\NotBlank(message="Необходимо заполнить пароль")
     */
    protected string $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string")
     */
    protected string $salt;

    /**
     * @var string
     * @ORM\Column(name="role", type="string")
     */
    protected string $roles;

    /**
     * @var ArrayCollection|NewsCategory[]
     * @ORM\ManyToMany(targetEntity="App\Entity\NewsCategory")
     */
    protected $newsCategories;

    /**
     * @var ArrayCollection|News[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\News")
     */
    protected $likedNews;

    public function __construct()
    {
        $this->setRoles(self::ROLE_USER);
        $this->newsCategories = new ArrayCollection();
        $this->likedNews = new ArrayCollection();
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
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return [self::ROLE_USER];
    }

    /**
     * @param string $roles
     */
    public function setRoles(string $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return NewsCategory[]|ArrayCollection
     */
    public function getNewsCategories()
    {
        return $this->newsCategories;
    }

    /**
     * @param string $name
     * @return false|NewsCategory
     */
    public function getCategoryByName(string $name)
    {
        return $this->getNewsCategories()->filter(
            function (NewsCategory $category) use ($name) {
                return $category->getEnglishName() === $name;
            }
        )->first();
    }

    /**
     * @param NewsCategory[]|ArrayCollection $newsCategories
     */
    public function setNewsCategories($newsCategories): void
    {
        $this->newsCategories = $newsCategories;
    }

    /**
     * @return News[]|ArrayCollection
     */
    public function getLikedNews()
    {
        return $this->likedNews;
    }

    /**
     * Понравившиеся за неделю новости
     *
     * @return ArrayCollection|News[]
     */
    public function getWeekLikedNews(): ArrayCollection
    {
        $lastWeek = strtotime("-1 week");

        return $this->getLikedNews()->filter(
            function (News $news) use ($lastWeek) {
                return $news->getPubDate() >= $lastWeek;
            }
        );
    }

    /**
     * @param News[]|ArrayCollection $likedNews
     */
    public function setLikedNews($likedNews): void
    {
        $this->likedNews = $likedNews;
    }

    /**
     * @param News $news
     */
    public function addLikedNews(News $news): void
    {
        if (!$this->likedNews->contains($news)){
            $this->likedNews->add($news);
        }
    }

    /**
     * @param News $news
     */
    public function removeLikedNews(News $news): void
    {
        if ($this->likedNews->contains($news)){
            $this->likedNews->removeElement($news);
        }
    }

    /**
     * @param News $news
     * @return bool
     */
    public function isLikedNews(News $news): bool
    {
        return $this->likedNews->contains($news);
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getLogin();
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function __toString()
    {
        return $this->getLogin();
    }
}
