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

namespace BaksDev\Finances\Repository\Statistics\Finance\Tests;

use BaksDev\Finances\Repository\Statistics\Finance\OrderFinanceInterface;
use BaksDev\Finances\Repository\Statistics\Finance\OrderFinanceResult;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


#[Group('order-finance-repository')]
#[When(env: 'test')]
class OrderFinanceRepositoryTest extends KernelTestCase
{
    public function testOrderFinanceRepository(): void
    {
        self::assertTrue(true);

        /** @var OrderFinanceInterface $OrderFinanceRepository */
        $OrderFinanceRepository = self::getContainer()->get(OrderFinanceInterface::class);

        $dayFrom = new DateTimeImmutable('now')->sub(DateInterval::createFromDateString('1 day'));
        $dayTo = new DateTimeImmutable('now')->sub(DateInterval::createFromDateString('1 day'));

        $result = $OrderFinanceRepository
            ->forUser(new UserUid())
            ->forPayment(new PaymentUid)
            ->dayFrom($dayFrom)
            ->dayTo($dayTo)
            ->findAll();

        if(false === $result || false === $result->valid())
        {
            return;
        }

        foreach($result as $OrderFinanceResult)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(OrderFinanceResult::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($OrderFinanceResult);
                    // dump($data);
                }
            }

            break;
        }

    }

}