<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Order;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;




#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')] 
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Le mot de passe ne peut pas être vide")
     * @Assert\Length(
     *      min=8,
     *      minMessage="Le mot de passe doit comporter au moins {{ limit }} caractères"
     * )
     * @Assert\Regex(
     *      pattern="/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/",
     *      message="Le mot de passe doit contenir au moins une lettre et un chiffre"
     * )
     */

    #[ORM\Column]
    private ?string $password = null;


      // Field to enable or disable user
     #[ORM\Column(type: 'boolean')]
     private bool $isActive = false;
 
     // Field for storing activation token
     #[ORM\Column(nullable: true)]
     private ?string $activationToken = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class, orphanRemoval: true)]
    private Collection $orders;

    /**
     * @var Collection<int, UserCoursePurchase>
     */
    #[ORM\OneToMany(targetEntity: UserCoursePurchase::class, mappedBy: 'user')]
    private Collection $userCoursePurchases;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->userCoursePurchases = new ArrayCollection();
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    // ✅ GETTERS / SETTERS POUR LES COMMANDES

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // Set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserCoursePurchase>
     */
    public function getUserCoursePurchases(): Collection
    {
        return $this->userCoursePurchases;
    }

    public function addUserCoursePurchase(UserCoursePurchase $userCoursePurchase): static
    {
        if (!$this->userCoursePurchases->contains($userCoursePurchase)) {
            $this->userCoursePurchases->add($userCoursePurchase);
            $userCoursePurchase->setUser($this);
        }

        return $this;
    }

    public function removeUserCoursePurchase(UserCoursePurchase $userCoursePurchase): static
    {
        if ($this->userCoursePurchases->removeElement($userCoursePurchase)) {
            // set the owning side to null (unless already changed)
            if ($userCoursePurchase->getUser() === $this) {
                $userCoursePurchase->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
   

    
    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    
    public function setActivationToken(?string $activationToken): self
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    public function isActive(): bool
{
    return $this->isActive;
}

public function setIsActive(bool $isActive): self
{
    $this->isActive = $isActive;

    return $this;
}
}

  
    

