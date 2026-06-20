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

namespace BaksDev\Finances\Repository\AllFinance;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Event\Invariable\FinancesInvariable;
use BaksDev\Finances\Entity\Event\Marketplace\FinancesMarketplace;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Finances\Entity\Event\Payment\FinancesPayment;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Orders\Order\Entity\Event\Posting\OrderPosting;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Payment\Entity\Event\PaymentEvent;
use BaksDev\Payment\Entity\Payment;
use BaksDev\Payment\Entity\Trans\PaymentTrans;
use BaksDev\Users\User\Repository\UserTokenStorage\UserTokenStorageInterface;
use BaksDev\Users\User\Type\Id\UserUid;


final class AllFinanceRepository implements AllFinanceInterface
{
    private ?SearchDTO $search = null;

    private UserUid|false $user = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserTokenStorageInterface $UserTokenStorage,
        private readonly PaginatorInterface $Paginator
    ) {}

    public function user(UserUid $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    /** Список всех платежей */
    public function findPaginator(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('finance.id')
            ->from(Finances::class, 'finance');

        $dbal
            ->addSelect('finances_invariable.usr as usr')
            ->addSelect('finances_invariable.created AS date')
            ->join(
                'finance',
                FinancesInvariable::class,
                'finances_invariable',
                'finances_invariable.main = finance.id AND finances_invariable.usr = :usr',
            )
            ->setParameter(
                key: 'usr',
                value: $this->user instanceof UserUid ? $this->user : $this->UserTokenStorage->getUser(),
                type: UserUid::TYPE,
            );

        $dbal
            ->addSelect('finances_marketplace.identifier AS identifier')
            ->addSelect('finances_marketplace.number AS number')
            ->leftJoin(
                'finance',
                FinancesMarketplace::class,
                'finances_marketplace',
                'finances_marketplace.main = finance.id',
            );

        $dbal
            ->addSelect('finances_payment.value AS payment_id')
            ->leftJoin(
                'finance',
                FinancesPayment::class,
                'finances_payment',
                'finances_payment.main = finance.id',
            );


        $dbal
            ->addSelect('finances_order.first AS first')
            ->leftJoin(
                'finance',
                FinancesOrder::class,
                'finances_order',
                'finances_order.main = finance.id',
            );

        $dbal
            ->addSelect('orders_posting.main AS order')
            ->addSelect('orders_posting.value AS posting')
            ->leftJoin(
                'finances_order',
                OrderPosting::class,
                'orders_posting',
                'orders_posting.main = finances_order.value',
            );


        $dbal
            ->addSelect('finances_event.price AS price')
            ->addSelect('finances_event.comment AS comment')
            ->leftJoin(
                'finance',
                FinancesEvent::class,
                'finances_event',
                'finances_event.id = finance.event',
            );

        if($this->search instanceof SearchDTO && $this->search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('finances_marketplace.number');
        }

        $dbal->addOrderBy('COALESCE(finances_order.first, finances_invariable.created)', 'DESC');
        $dbal->addOrderBy('finances_invariable.created', 'DESC');
        $dbal->addOrderBy('finances_invariable.main', 'DESC');

        return $this->Paginator->fetchAllHydrate($dbal, FinanceResult::class);

    }
}