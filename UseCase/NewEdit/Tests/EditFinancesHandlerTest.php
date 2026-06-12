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

namespace BaksDev\Finances\UseCase\NewEdit\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Finances;
use BaksDev\Finances\Repository\CurrentFinancesEvent\CurrentFinancesEventInterface;
use BaksDev\Finances\Type\Id\FinancesUid;
use BaksDev\Finances\UseCase\NewEdit\NewEditFinancesDTO;
use BaksDev\Finances\UseCase\NewEdit\NewEditFinancesHandler;
use BaksDev\Finances\UseCase\NewEdit\Payment\NewEditPaymentDTO;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Uid\Uuid;


#[Group('finances')]
#[When(env: 'test')]
class EditFinancesHandlerTest extends KernelTestCase
{
    #[DependsOnClass(NewFinancesHandlerTest::class)]
    public function testUseCase(): void
    {
        /** @var CurrentFinancesEventInterface $CurrentFinancesEventRepository */
        $CurrentFinancesEventRepository = self::getContainer()->get(CurrentFinancesEventInterface::class);
        $FinancesEvent = $CurrentFinancesEventRepository->forFinanceMain(new FinancesUid(FinancesUid::TEST))->find();
        self::assertInstanceOf(FinancesEvent::class, $FinancesEvent);


        /** @see FinancesDTO */
        $NewEditFinancesDTO = new NewEditFinancesDTO();
        $FinancesEvent->getDto($NewEditFinancesDTO);
        self::assertTrue($NewEditFinancesDTO->getPrice()->equals(-101.43));

        $NewEditFinancesInvariableDTO = $NewEditFinancesDTO->getInvariable();
        self::assertTrue($NewEditFinancesInvariableDTO->getUsr()->equals(UserUid::TEST));

        $NewEditFinancesMarketplaceDTO = $NewEditFinancesDTO->getMarketpace();
        self::assertEquals(21212132321, $NewEditFinancesMarketplaceDTO->getNumber());
        self::assertEquals(8979878798, $NewEditFinancesMarketplaceDTO->getIdentifier());
        self::assertTrue($NewEditFinancesMarketplaceDTO->getToken()->equals(new Uuid(UserUid::TEST)));

        $NewEditPaymentDTO = $NewEditFinancesDTO->getPayment();
        self::assertTrue($NewEditPaymentDTO->getValue()->equals(PaymentUid::TEST));

        /** Обновляем объект */

        $NewEditFinancesDTO
            ->setPrice(new Money(101.43))
            ->setComment('Комментарий');

        $NewEditFinancesInvariableDTO = $NewEditFinancesDTO->getInvariable();
        $NewEditFinancesInvariableDTO->setUsr(clone new UserUid(UserUid::TEST));

        $NewEditFinancesOrderDTO = $NewEditFinancesDTO->getOrd();
        $NewEditFinancesOrderDTO->setValue(new OrderUid(OrderUid::TEST));

        $NewEditFinancesMarketplaceDTO = $NewEditFinancesDTO->getMarketpace();
        $NewEditFinancesMarketplaceDTO
            ->setToken(new Uuid(UserUid::TEST_USER))
            ->setNumber(23323232232)
            ->setIdentifier(4545454545);


        $NewEditPaymentDTO->setValue(new PaymentUid(PaymentUid::TEST));


        /** @var NewEditFinancesHandler $NewEditFinancesHandler */
        $NewEditFinancesHandler = self::getContainer()->get(NewEditFinancesHandler::class);
        $handle = $NewEditFinancesHandler->handle($NewEditFinancesDTO);

        self::assertTrue(($handle instanceof Finances), $handle.': Ошибка Finances');

    }
}