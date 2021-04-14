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
    private const ROLE_ADMIN = 'ROLE_ADMIN';

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
     * @Assert\NotBlank(message="Необходимо заполнить поле")
     */
    protected string $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string")
     * @Assert\NotBlank(message="Необходимо заполнить поле")
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

//    /**
//     * @var ArrayCollection|NewsCategory[]
//     * @ORM\ManyToMany(targetEntity="NewsCategory")
//     */
//    protected $newsCategories;

    public function __construct()
    {
        $this->setRoles(self::ROLE_USER);
//        $this->newsCategories = new ArrayCollection();
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

//    /**
//     * @return NewsCategory[]|ArrayCollection
//     */
//    public function getNewsCategories()
//    {
//        return $this->newsCategories;
//    }
//
//    /**
//     * @param NewsCategory[]|ArrayCollection $newsCategories
//     */
//    public function setNewsCategories($newsCategories): void
//    {
//        $this->newsCategories = $newsCategories;
//    }

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
