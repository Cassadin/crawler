<?php

namespace App\Entity;

use App\Repository\FieldsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FieldsRepository::class)
 */
class Fields
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $starts;

    /**
     * @ORM\Column(type="integer")
     */
    private $ends;

    /**
     * @ORM\Column(type="integer")
     */
    private $pixelsR;

    /**
     * @ORM\Column(type="integer")
     */
    private $pixelsG;

    /**
     * @ORM\Column(type="integer")
     */
    private $pixelsB;
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStarts(): ?int
    {
        return $this->starts;
    }

    public function setStarts(int $starts): self
    {
        $this->starts = $starts;

        return $this;
    }

    public function getEnds(): ?int
    {
        return $this->ends;
    }

    public function setEnds(int $ends): self
    {
        $this->ends = $ends;

        return $this;
    }

    public function getPixelsR(): ?int
    {
        return $this->pixelsR;
    }

    public function setPixelsR(int $pixelsR): self
    {
        $this->pixelsR = $pixelsR;

        return $this;
    }

    public function getPixelsG(): ?int
    {
        return $this->pixelsG;
    }

    public function setPixelsG(int $pixelsG): self
    {
        $this->pixelsG = $pixelsG;

        return $this;
    }

    public function getPixelsB(): ?int
    {
        return $this->pixelsB;
    }

    public function setPixelsB(int $pixelsB): self
    {
        $this->pixelsB = $pixelsB;

        return $this;
    }
}
