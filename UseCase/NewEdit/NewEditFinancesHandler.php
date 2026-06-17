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

namespace BaksDev\Finances\UseCase\NewEdit;


use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Event\Order\FinancesOrder;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Finances\Messenger\Default\FinancesMessage;
use BaksDev\Finances\UseCase\NewEdit\Order\NewEditFinancesOrderDTO;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
final class NewEditFinancesHandler extends AbstractHandler
{
    /** @see Finances */
    public function handle(NewEditFinancesDTO $command): string|Finances
    {
        $this->setCommand($command);

        $this->preEventPersistOrUpdate(Finances::class, FinancesEvent::class);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        /** Получаем все платежи по заказу и обновляем дату последнего платежа First */
        if($command->getOrd()->getValue() instanceof OrderUid)
        {
            $orders = $this
                ->getRepository(FinancesOrder::class)
                ->findBy(['value' => $command->getOrd()->getValue()]);

            foreach($orders as $FinancesOrder)
            {
                $NewEditFinancesOrderDTO = new NewEditFinancesOrderDTO();
                $FinancesOrder->getDto($NewEditFinancesOrderDTO);

                $NewEditFinancesOrderDTO->setFirst($command->getInvariable()->getCreated());
                $FinancesOrder->setEntity($NewEditFinancesOrderDTO);
            }
        }

        $this->flush();

        /* Отправляем отложенное сообщение в шину, чтобы учитывать все транзакции */
        $this->messageDispatch->dispatch(
            message: new FinancesMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            stamps: [new MessageDelay('60 minutes')],
            transport: 'finances-low',
        );

        return $this->main;
    }
}