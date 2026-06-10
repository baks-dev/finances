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

namespace BaksDev\Finances\Repository\CurrentFinancesEvent;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Finances\Type\Id\FinancesUid;
use InvalidArgumentException;

final class CurrentFinancesEventRepository implements CurrentFinancesEventInterface
{
    private FinancesUid|false $main = false;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function forFinanceMain(FinancesUid $main): self
    {
        $this->main = $main;
        return $this;
    }

    /** Метод возвращает текущее событие платежа */
    public function find(): ?FinancesEvent
    {
        if(false === ($this->main instanceof FinancesUid))
        {
            throw new InvalidArgumentException('Invalid Argument FinancesUid');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);


        $orm
            ->from(Finances::class, 'finances')
            ->where('finances.id = :finance')
            ->setParameter(
                key: 'finance',
                value: $this->main,
                type: FinancesUid::TYPE,
            );

        $orm
            ->select('event')
            ->join(
                FinancesEvent::class,
                'event',
                'WITH',
                'event.id = finances.event',
            );


        return $orm->getQuery()->getOneOrNullResult();
    }
}
