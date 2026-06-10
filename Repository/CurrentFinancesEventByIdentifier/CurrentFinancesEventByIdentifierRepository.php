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

namespace BaksDev\Finances\Repository\CurrentFinancesEventByIdentifier;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Finances\Entity\Event\FinancesEvent;
use BaksDev\Finances\Entity\Event\Marketplace\FinancesMarketplace;
use Doctrine\DBAL\ParameterType;


final readonly class CurrentFinancesEventByIdentifierRepository implements CurrentFinancesEventByIdentifierInterface
{
    public function __construct(private ORMQueryBuilder $ORMQueryBuilder) {}

    public function find(int|string $identifier): ?FinancesEvent
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->from(FinancesMarketplace::class, 'finances_marketplace')
            ->where('finances_marketplace.identifier = :identifier')
            ->setParameter(
                key: 'identifier',
                value: $identifier,
                type: ParameterType::STRING,
            );

        $orm
            ->select('event')
            ->join(
                FinancesEvent::class,
                'event',
                'WITH',
                'event.id = finances_marketplace.event',
            );

        return $orm->getQuery()->getOneOrNullResult();
    }
}