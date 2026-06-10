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

namespace BaksDev\Finances\Entity\Event\Order;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Type\Id\FinancesUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* FinancesOrder */

#[ORM\Entity]
#[ORM\Table(name: 'finances_order')]
#[ORM\Index(columns: ['value'])]
class FinancesOrder extends EntityReadonly
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
    #[ORM\OneToOne(targetEntity: FinancesEvent::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private FinancesEvent $event;

    /** Значение свойства */
    #[Assert\NotBlank]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $value;


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

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof FinancesOrderInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof FinancesOrderInterface || $dto instanceof self)
        {
            if(false === ($dto->getValue() instanceof OrderUid))
            {
                return false;
            }

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getValue(): OrderUid
    {
        return $this->value;
    }

}