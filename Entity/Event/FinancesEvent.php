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

namespace BaksDev\Finances\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityState;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Finances\Entity\Event\Invariable\FinancesInvariable;
use BaksDev\Finances\Entity\Event\Marketplace\FinancesMarketplace;
use BaksDev\Finances\Entity\Event\Modify\FinancesModify;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Finances\Entity\Event\Payment\FinancesPayment;
use BaksDev\Finances\Entity\Event\Product\FinancesProduct;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Finances\Type\Event\FinancesEventUid;
use BaksDev\Finances\Type\Id\FinancesUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* FinancesEvent */

#[ORM\Entity]
#[ORM\Table(name: 'finances_event')]
class FinancesEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: FinancesEventUid::TYPE)]
    private FinancesEventUid $id;

    /**
     * Идентификатор Finances
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: FinancesUid::TYPE, nullable: false)]
    private ?FinancesUid $main = null;

    /** FinancesInvariable */
    #[ORM\OneToOne(targetEntity: FinancesInvariable::class, mappedBy: 'event', cascade: ['all'])]
    private ?FinancesInvariable $invariable = null;

    /** FinancesOrder */
    #[ORM\OneToOne(targetEntity: FinancesOrder::class, mappedBy: 'event', cascade: ['all'])]
    private ?FinancesOrder $ord = null;

    /** FinancesProduct */
    #[ORM\OneToOne(targetEntity: FinancesProduct::class, mappedBy: 'event', cascade: ['all'])]
    private ?FinancesProduct $product = null;

    /** FinancesMarketplace */
    #[ORM\OneToOne(targetEntity: FinancesMarketplace::class, mappedBy: 'event', cascade: ['all'])]
    private ?FinancesMarketplace $marketpace = null;

    /** FinancesPayment */
    #[ORM\OneToOne(targetEntity: FinancesPayment::class, mappedBy: 'event', cascade: ['all'])]
    private ?FinancesPayment $payment = null;

    /** Стоимость */
    #[Assert\NotBlank]
    #[ORM\Column(type: Money::TYPE)]
    private Money $price;

    /** Комментарий */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $comment;


    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: FinancesModify::class, mappedBy: 'event', cascade: ['all'])]
    private FinancesModify $modify;


    public function __construct()
    {
        $this->id = new FinancesEventUid();
        $this->modify = new FinancesModify($this);
    }

    /**
     * Идентификатор События
     */

    public function __clone()
    {
        $this->id = clone new FinancesEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getMain(): ?FinancesUid
    {
        return $this->main;
    }

    /**
     * Идентификатор Finances
     */
    public function setMain(FinancesUid|Finances $main): void
    {
        $this->main = $main instanceof Finances ? $main->getId() : $main;
    }

    public function getId(): FinancesEventUid
    {
        return $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof FinancesEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof FinancesEventInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getInvariable(): ?FinancesInvariable
    {
        return $this->invariable;
    }

    public function isOrders(): bool
    {
        return $this->ord instanceof FinancesOrder;
    }

    public function getOrderFinance(): OrderUid
    {
        return $this->ord->getOrderId();
    }


    public function isMarketpace(): bool
    {
        return $this->marketpace instanceof FinancesMarketplace;
    }

    public function isProduct(): bool
    {
        return $this->product instanceof FinancesProduct;
    }
}