<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\MappedSuperclass]
abstract class BaseUser implements UserInterface
{
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    protected string $email;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    protected string $username;

    #[ORM\Column(type: 'boolean')]
    protected bool $enabled;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    protected ?string $confirmationToken;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private DateTime $passwordRequestedAt;

    #[ORM\Column(type: 'json')]
    protected ?array $roles = [];

    /**
     * The hashed password.
     */
    #[ORM\Column(type: 'string')]
    protected string $password;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified;

    public function __construct()
    {
        $this->username = '';
        $this->email = '';
        $this->isVerified = false;
        $this->enabled = false;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower($email);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setConfirmationToken(string $token): self
    {
        $this->enabled = false;
        $this->confirmationToken = $token;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function confirmUser(): self
    {
        $this->enabled = true;
        $this->confirmationToken = null;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
