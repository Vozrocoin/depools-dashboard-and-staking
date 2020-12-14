<?php

namespace App\Entity;

use App\Repository\DepoolRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="depool",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="net_id__address",
 *            columns={"net_id", "address"})
 *    }
 * )
 * @ORM\Entity(repositoryClass=DepoolRepository::class)
 */
class Depool
{
    public const CODE_HASH = 'b4ad6c42427a12a65d9a0bffb0c2730dd9cdf830a086d94636dab7784e13eb38';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Net", inversedBy="depools")
     * @ORM\JoinColumn(name="net_id", referencedColumnName="id")
     */
    private $net;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=67)
     */
    private $address;

    /**
     * @ORM\Column(type="json")
     */
    private $info;

    /**
     * @ORM\Column(type="json")
     */
    private $stakes;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $isDeleted = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdTs;

    /**
     * @ORM\OneToMany(targetEntity="DepoolRound", mappedBy="depool")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $rounds;

    /**
     * @ORM\OneToMany(targetEntity="DepoolEvent", mappedBy="depool")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $events;

    public function __construct(Net $net, string $address, array $info, array $stakes)
    {
        $this->net = $net;
        $this->address = $address;
        $this->info = $info;
        $this->stakes = $stakes;
        $this->createdTs = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCreatedTs(): ?\DateTimeInterface
    {
        return $this->createdTs;
    }

    /**
     * @return Collection|DepoolRound[]
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function setRounds($rounds): void
    {
        $this->rounds = $rounds;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function setStakes(array $stakes): void
    {
        $this->stakes = $stakes;
    }

    public function getStakes(): array
    {
        return $this->stakes;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getNet(): Net
    {
        return $this->net;
    }

    public function compileStakes()
    {
        $stakes = $this->getStakes();
        $total = '0';
        $items = [];
        foreach ($stakes as $stake) {
            $total = bcadd($total, hexdec($stake['info']['total']));
            $items[] = [
                'address' => $stake['address'],
                'info' => [
                    'total' => hexdec($stake['info']['total']),
                    'withdrawValue' => hexdec($stake['info']['withdrawValue']),
                    'reinvest' => $stake['info']['reinvest'],
                    'reward' => hexdec($stake['info']['reward']),
                ],
            ];
        }

        return [
            'participantsNum' => count($stakes),
            'total' => $total,
            'items' => $items
        ];
    }

    public function compileParams()
    {
        return [
            'minStake' => hexdec($this->getInfo()['minStake']),
            'validatorAssurance' => hexdec($this->getInfo()['validatorAssurance']),
            'participantRewardFraction' => hexdec($this->getInfo()['participantRewardFraction']),
            'validatorRewardFraction' => hexdec($this->getInfo()['validatorRewardFraction']),
            'balanceThreshold' => hexdec($this->getInfo()['balanceThreshold']),
            'poolClosed' => $this->getInfo()['poolClosed'],
        ];
    }

    public function compileLink()
    {
        return sprintf(
            "https://%s/accounts?section=details&id=%s",
            $this->getNet()->getExplorer(),
            $this->getAddress()
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function setIsDeleted(): void
    {
        $this->isDeleted = true;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }
}
