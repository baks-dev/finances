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

namespace BaksDev\Finances\Repository\Statistics\Orders;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Event\Invariable\FinancesInvariable;
use BaksDev\Finances\Entity\Event\Marketplace\FinancesMarketplace;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Finances\Entity\Event\Payment\FinancesPayment;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;


final class StatisticsOrdersRepository implements StatisticsOrdersInterface
{
    private DateTimeImmutable|false $day_from = false;
    private DateTimeImmutable|false $day_to = false;

    private bool $cache = false;
    private bool $hold = false;
    private bool|null $orders = null;

    private PaymentUid|false $payment = false;

    private UserUid|false $user = false;


    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forUser(UserUid $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function forPayment(PaymentUid $payment): self
    {
        $this->payment = $payment;
        return $this;
    }

    /** Дата начала периода */
    public function dayFrom(DateTimeImmutable $day): self
    {
        $this->day_from = $day->setTime(0, 0, 0);
        return $this;
    }

    /** Дата окончания периода */
    public function dayTo(DateTimeImmutable $day): self
    {
        $this->day_to = $day->setTime(23, 59, 59);
        return $this;
    }

    /** Только по имеющимся заказам */
    public function onlyOrders(): self
    {
        $this->orders = true;
        return $this;
    }

    /** Только по имеющимся заказам */
    public function onlyNotOrders(): self
    {
        $this->orders = false;
        return $this;
    }


    /** Положительный баланс */
    public function onlyCache(): self
    {
        $this->cache = true;
        return $this;
    }

    /** Отрицательный баланс */
    public function onlyHold(): self
    {
        $this->hold = true;
        return $this;
    }

    /**
     * @return StatisticsOrdersResult|false
     */
    public function find(): StatisticsOrdersResult|false
    {
        if(false === ($this->payment instanceof PaymentUid))
        {
            throw new InvalidArgumentException('Invalid Argument PaymentUid');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('finances_payment.value AS payment')
            ->from(FinancesPayment::class, 'finances_payment')
            ->where('finances_payment.value = :payment')
            ->setParameter(
                key: 'payment',
                value: $this->payment,
                type: PaymentUid::TYPE,
            );


        if($this->orders === true)
        {
            $dbal->join(
                'finances_payment',
                FinancesOrder::class,
                'finances_order',
                'finances_order.main = finances_payment.main',
            );
        }

        if($this->orders === false)
        {
            $dbal->leftJoin(
                'finances_payment',
                FinancesOrder::class,
                'finances_order',
                'finances_order.main = finances_payment.main',
            );

            $dbal->where('finances.id IS NULL');
        }

        $dbal->join(
            'finances_payment',
            Finances::class,
            'finances',
            'finances.id = finances_payment.main',
        );

        if(true === ($this->day_from instanceof DateTimeImmutable) && true === ($this->day_to instanceof DateTimeImmutable))
        {
            $dbal
                ->addSelect('finances_invariable.usr AS project_user')
                ->join(
                    'finances_payment',
                    FinancesInvariable::class,
                    'finances_invariable',
                    '
                    finances_invariable.main = finances_payment.main
                    AND DATE(finances_invariable.created) BETWEEN :date_from AND :date_to
                ')
                ->setParameter('date_from', $this->day_from, Types::DATE_IMMUTABLE)
                ->setParameter('date_to', $this->day_to, Types::DATE_IMMUTABLE);
        }

        $dbal
            ->join(
                'finances_payment',
                FinancesMarketplace::class,
                'finances_marketplace',
                'finances_marketplace.main = finances_payment.main',
            );

        $dbal
            ->addSelect('SUM(finances_event.price) AS total')
            ->join(
                'finances_payment',
                FinancesEvent::class,
                'finances_event',
                'finances_event.id = finances_payment.event'
                .($this->cache ? ' AND finances_event.price > 0' : '')
                .($this->hold ? ' AND finances_event.price < 0' : ''),
            );

        $dbal->allGroupByExclude();

        $result = $dbal->fetchHydrate(StatisticsOrdersResult::class);

        $this->orders = null;

        return $result;

    }
}