<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: "koi_log")]
#[ApiResource(
    normalizationContext: ["groups" => ["log:read"]],
    denormalizationContext: ["groups" => ["log:write"]],
    collectionOperations: ["get"],
    itemOperations: ["get"]
)]
class Log
{
    #[ORM\Id]
    #[ORM\Column(type: "string", length: 36, unique: true, options: ["fixed" => true])]
    #[Groups(["log:read"])]
    private string $id;

    #[ORM\Column(type: "string", length: 6, nullable: true)]
    #[Groups(["log:read"])]
    private ?string $type;

    #[ORM\Column(type: "datetime")]
    #[Groups(["log:read"])]
    private ?\DateTimeInterface $loggedAt = null;

    #[ORM\Column(type: "string", length: 36)]
    #[Groups(["log:read"])]
    private string $objectId;

    #[ORM\Column(type: "string")]
    #[Groups(["log:read"])]
    private string $objectLabel;

    #[ORM\Column(type: "string")]
    #[Groups(["log:read"])]
    private string $objectClass;

    #[ORM\Column(type: "boolean", options: ["default" => 0])]
    #[Groups(["log:read"])]
    private bool $objectDeleted;

    #[ORM\Column(type: "text", nullable: true)]
    #[Groups(["log:read"])]
    private ?string $payload;

    #[ORM\ManyToOne(targetEntity: "User", inversedBy: "logs")]
    #[Groups(["log:read"])]
    private ?User $owner;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->objectDeleted = false;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLoggedAt(): ?\DateTimeInterface
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(\DateTimeInterface $loggedAt): self
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): self
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getObjectLabel(): ?string
    {
        return $this->objectLabel;
    }

    public function setObjectLabel(string $objectLabel): self
    {
        $this->objectLabel = $objectLabel;

        return $this;
    }

    public function getObjectClass(): ?string
    {
        return $this->objectClass;
    }

    public function setObjectClass(string $objectClass): self
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    public function isObjectDeleted(): ?bool
    {
        return $this->objectDeleted;
    }

    public function setObjectDeleted(bool $objectDeleted): self
    {
        $this->objectDeleted = $objectDeleted;

        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
