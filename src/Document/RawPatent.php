<?php

namespace App\Document;

use App\Repository\PatentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'raw_patents', repositoryClass: PatentRepository::class)]
class RawPatent
{
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: 'int')]
    private $year;

    #[ODM\Field(type: 'hash', name: 'us-patent-grant')]
    private $usPatentGrant= [];

    // Getters and setters

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getusPatentGrant(): array
    {
        return $this->usPatentGrant;
    }

    public function setUsPatentGrant(array $usPatentGrant): self
    {
        $this->usPatentGrant = $usPatentGrant;
        return $this;
    }
}