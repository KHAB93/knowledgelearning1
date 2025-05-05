<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Product;
use App\Entity\Lesson;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Lesson::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Certification::class)]
    private Collection $certifications;

   

    /**
     * @return Collection<int, Certification>
     */
    public function getCertifications(): Collection
    {
        return $this->certifications;
    }


    /**
     * @var Collection<int, Product>
     */
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }


     /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons[] = $lesson;
            $lesson->setCourse($this); 
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            if ($lesson->getCourse() === $this) {
                $lesson->setCourse(null);
            }
        }

        return $this;
    }

   

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'course')]
    private Collection $OrderItem;

    /**
     * @var Collection<int, UserCoursePurchase>
     */
    #[ORM\OneToMany(targetEntity: UserCoursePurchase::class, mappedBy: 'course')]
    private Collection $userCoursePurchases;

    public function __construct()
    {
        $this->OrderItem = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->userCoursePurchases = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->certifications = new ArrayCollection();

    }


    public function getOrderItem(): Collection
    {
        return $this->OrderItem;
    }

    public function addOrderProduct(OrderItem $orderProduct): static
{
    if (!$this->OrderItem->contains($orderProduct)) {
        $this->OrderItem[] = $orderProduct;
        $orderProduct->setCourse($this); 
    }

    return $this;
}

public function removeOrderProduct(OrderItem $orderProduct): static
{
    if ($this->OrderItem->removeElement($orderProduct)) {
        if ($orderProduct->getCourse() === $this) {
            $orderProduct->setCourse(null);
        }
    }

    return $this;
}

public function getProducts(): Collection
{
    return $this->products;
}

public function addProduct(Product $product): static
{
    if (!$this->products->contains($product)) {
        $this->products[] = $product;
        $product->setCourse($this);
    }

    return $this;
}

public function removeProduct(Product $product): static
{
    if ($this->products->removeElement($product)) {
        if ($product->getCourse() === $this) {
            $product->setCourse(null);
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
        $userCoursePurchase->setCourse($this);
    }

    return $this;
}

public function removeUserCoursePurchase(UserCoursePurchase $userCoursePurchase): static
{
    if ($this->userCoursePurchases->removeElement($userCoursePurchase)) {
        // set the owning side to null (unless already changed)
        if ($userCoursePurchase->getCourse() === $this) {
            $userCoursePurchase->setCourse(null);
        }
    }

    return $this;
}







}
