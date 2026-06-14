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

namespace BaksDev\Finances\Repository\Statistics\Finance;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Finances\Entity\Event\Invariable\FinancesInvariable;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Finances\Entity\Event\Payment\FinancesPayment;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Orders\Order\Entity\Event\Finance\OrderFinance;
use BaksDev\Orders\Order\Entity\Event\Posting\OrderPosting;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\Price\ProductOfferPrice;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Price\ProductModificationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\Price\ProductVariationPrice;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Price\ProductPrice;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Generator;


final class OrderFinanceRepository implements OrderFinanceInterface
{
    private DateTimeImmutable|false $day_from = false;
    private DateTimeImmutable|false $day_to = false;

    private PaymentUid $payment;

    private UserUid $user;

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


    /**
     * Метод считает разницу между стоимостью товара в заказе и конечной стоимостью реализации
     *
     * @return Generator<OrderFinanceResult>|false
     */
    public function findAll(): Generator|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(FinancesPayment::class, 'finances_payment')
            ->where('finances_payment.value = :payment')
            ->setParameter(
                key: 'payment',
                value: $this->payment,
                type: PaymentUid::TYPE,
            );

        $dbal->join(
            'finances_payment',
            FinancesOrder::class,
            'finances_order',
            'finances_order.main = finances_payment.main',
        );

        $dbal
            ->addSelect('orders.id AS order_id')
            ->leftJoin(
                'finances_order',
                Order::class,
                'orders',
                'orders.id = finances_order.value',
            );

        $dbal
            ->leftJoin(
                'orders',
                OrderPosting::class,
                'orders_posting',
                'orders_posting.main = orders.id',
            );

        $dbal
            ->leftJoin(
                'orders',
                OrderProduct::class,
                'order_product',
                'order_product.event = orders.event',
            );


        $dbal
            ->leftJoin(
                'order_product',
                OrderPrice::class,
                'orders_price',
                'orders_price.product = order_product.id',
            );


        $dbal->join(
            'order_product',
            ProductEvent::class,
            'product_event',
            'product_event.id = order_product.product',
        );

        /** Торговое предложение */
        $dbal->leftJoin(
            'product_event',
            ProductOffer::class,
            'product_offer',
            'product_offer.id = order_product.offer 
            AND product_offer.event = product_event.id',
        );

        /** Множественный вариант */
        $dbal->leftJoin(
            'product_offer',
            ProductVariation::class,
            'product_variation',
            'product_variation.id = order_product.variation 
            AND product_variation.offer = product_offer.id',
        );

        /** Модификация множественного варианта */
        $dbal->leftJoin(
            'product_variation',
            ProductModification::class,
            'product_modification',
            'product_modification.id = order_product.modification 
            AND product_modification.variation = product_variation.id',
        );


        /* Базовая Цена товара */
        $dbal->leftJoin(
            'product_event',
            ProductPrice::class,
            'product_price',
            'product_price.event = product_event.id',
        );

        /* Цена торгового предо жения */
        $dbal->leftJoin(
            'product_offer',
            ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id',
        );

        /* Цена множественного варианта */
        $dbal->leftJoin(
            'product_variation',
            ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_variation.id',
        );

        /* Цена модификации множественного варианта */
        $dbal->leftJoin(
            'product_modification',
            ProductModificationPrice::class,
            'product_modification_price',
            'product_modification_price.modification = product_modification.id',
        );

        $dbal->addSelect('
			COALESCE(
                NULLIF(product_modification_price.price, 0), 
                NULLIF(product_variation_price.price, 0), 
                NULLIF(product_offer_price.price, 0), 
                NULLIF(product_price.price, 0),
                0
            ) * orders_price.total AS order_price
		');

        $dbal
            ->addSelect('order_finance.value AS order_finance')
            ->leftJoin(
                'order_product',
                OrderFinance::class,
                'order_finance',
                'order_finance.main = orders.id',
            );


        $dbal->join(
            'finances_payment',
            Finances::class,
            'finances',
            'finances.id = finances_payment.main',
        );

        if(
            true === ($this->day_from instanceof DateTimeImmutable)
            && true === ($this->day_to instanceof DateTimeImmutable)
        )
        {
            $dbal
                ->join(
                    'finances_payment',
                    FinancesInvariable::class,
                    'finances_invariable',
                    '
                    finances_invariable.main = finances_payment.main
                    AND finances_invariable.usr = :usr
                    AND DATE(finances_invariable.created) BETWEEN :date_from AND :date_to
                ')
                ->setParameter(
                    key: 'usr',
                    value: $this->user,
                    type: UserUid::TYPE,
                )
                ->setParameter('date_from', $this->day_from, Types::DATE_IMMUTABLE)
                ->setParameter('date_to', $this->day_to, Types::DATE_IMMUTABLE);
        }

        $dbal->allGroupByExclude();

        return $dbal->fetchAllHydrate(OrderFinanceResult::class);
    }
}