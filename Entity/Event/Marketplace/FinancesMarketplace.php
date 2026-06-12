<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Finances\Entity\Event\Marketplace;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Core\Type\UidType\UidType;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Type\Id\FinancesUid;
use BaksDev\Payment\Type\Id\PaymentUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/* FinancesMarketplace */

#[ORM\Entity]
#[ORM\Table(name: 'finances_marketplace')]
#[ORM\Index(columns: ['number'])]
class FinancesMarketplace extends EntityReadonly
{
    /**
     * Идентификатор Main
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: FinancesUid::TYPE)]
    private FinancesUid $main;

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: FinancesEvent::class, inversedBy: 'marketpace')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private FinancesEvent $event;

    /** Идентификатор внешнего сервиса */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, unique: true)]
    private string $identifier;

    /** Идентификатор заказа или услуги */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING)]
    private string $number;

    /** ID токена маркетплейса */
    #[Assert\NotBlank]
    #[ORM\Column(type: UidType::TYPE, nullable: true)]
    private Uuid|null $token = null;

    public function __construct(FinancesEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function setEvent(FinancesEvent $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getToken(): ?Uuid
    {
        return $this->token;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof FinancesMarketplaceInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof FinancesMarketplaceInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}