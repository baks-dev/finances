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

namespace BaksDev\Finances\Repository\AllFinanceByOrder;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Reference\Money\Type\Money;
use Generator;
use InvalidArgumentException;


final class AllFinanceByOrderRepository implements AllFinanceByOrderInterface
{
    private OrderUid|false $order = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forOrder(OrderUid $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Получает все выплаты по заказам
     *
     * @return Generator<Money>|bool
     */
    public function findAll(): Generator|bool
    {
        if(false === ($this->order instanceof OrderUid))
        {
            throw new InvalidArgumentException('Не указан идентификатор заказа');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        //$dbal->select('id');
        $dbal
            ->from(FinancesOrder::class, 'finances_order')
            ->where('finances_order.value = :order')
            ->setParameter(
                key: 'order',
                value: $this->order,
                type: OrderUid::TYPE,
            );

        $dbal
            ->addSelect('finances_event.price AS value')
            ->leftJoin(
                'finances_order',
                FinancesEvent::class,
                'finances_event',
                'finances_event.id = finances_order.event',
            );


        return $dbal->fetchAllHydrate(Money::class);
    }
}