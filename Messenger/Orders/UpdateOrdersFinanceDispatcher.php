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

namespace BaksDev\Finances\Messenger\Orders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Messenger\Default\FinancesMessage;
use BaksDev\Finances\Repository\AllFinanceByOrder\AllFinanceByOrderInterface;
use BaksDev\Finances\Repository\CurrentFinancesEvent\CurrentFinancesEventInterface;
use BaksDev\Orders\Order\Entity\Event\Finance\OrderFinance;
use BaksDev\Orders\Order\UseCase\Admin\Finance\OrderFinanceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Finance\OrderFinanceHandler;
use BaksDev\Reference\Money\Type\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Обновляет финансовые выплаты по заказу */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final readonly class UpdateOrdersFinanceDispatcher
{
    public function __construct(
        #[Target('financesLogger')] private LoggerInterface $logger,
        private DeduplicatorInterface $deduplicator,
        private CurrentFinancesEventInterface $currentFinancesEventRepository,
        private AllFinanceByOrderInterface $AllFinanceByOrderRepository,
        private OrderFinanceHandler $OrderFinanceHandler,
    ) {}

    public function __invoke(FinancesMessage $message): void
    {
        $DeduplicatorExecute = $this->deduplicator
            ->namespace('finances')
            ->expiresAfter('1 day')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($DeduplicatorExecute->isExecuted())
        {
            return;
        }

        /** Получаем информацию о платеже */

        $FinancesEvent = $this->currentFinancesEventRepository
            ->forFinanceMain($message->getId())
            ->find();

        if(false === ($FinancesEvent instanceof FinancesEvent))
        {
            $this->logger->critical(
                'finances: Ошибка при получении финансовой выплаты',
                [self::class.':'.__LINE__],
            );

            return;
        }

        if(false === $FinancesEvent->isOrders() || false === $FinancesEvent->isMarketpace())
        {
            $DeduplicatorExecute->save();
            return;
        }


        /** Получаем сумму финансовых выплат по заказу */

        $moneys = $this->AllFinanceByOrderRepository
            ->forOrder($FinancesEvent->getOrderFinance())
            ->findAll();

        $total = 0;

        foreach($moneys as $money)
        {
            if($money->getValue() > 0)
            {
                $total += $money->getValue(multiply: true);
            }

            if($money->getValue() < 0)
            {
                $total -= $money->getValue(multiply: true);
            }
        }


        $Deduplicator = $this->deduplicator
            ->namespace('finances')
            ->expiresAfter('1 day')
            ->deduplication([
                (string) $FinancesEvent->getOrderFinance(),
                $total,
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /** Обновляем экономику заказа */
        $OrderFinanceDTO = new OrderFinanceDTO()
            ->setMain($FinancesEvent->getOrderFinance())
            ->setValue(new Money($total, division: true));

        $OrderFinance = $this->OrderFinanceHandler->handle($OrderFinanceDTO);

        if(false === ($OrderFinance instanceof OrderFinance))
        {
            $this->logger->critical(
                sprintf('finances: Ошибка %s при обновлении финансовой выплаты заказа', $OrderFinance),
                [self::class.':'.__LINE__, $FinancesEvent->getOrderFinance()],
            );

            return;
        }

        $this->logger->info(
            'Обновили информацию о финансовых выплатах заказа',
            [self::class.':'.__LINE__, $FinancesEvent->getOrderFinance()],
        );

        $Deduplicator->save();
    }
}
