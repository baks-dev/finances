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

namespace BaksDev\Finances\Repository\Statistics\Total;

use BaksDev\Core\Doctrine\DBALQueryBuilder;


interface TotalInterface
{
    public function forDay(): self;

    public function forMonth(): self;

    public function forYear(): self;

    public function onlyCache(): self;

    public function onlyHold(): self;

    public function findAll(): array|bool;
}

final class CashTotalRepository implements TotalInterface
{
    private bool $day = false;
    private bool $month = false;
    private bool $year = false;

    private bool $cache = false;
    private bool $hold = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forDay(): self
    {
        $this->day = true;
        return $this;
    }

    public function forMonth(): self
    {
        $this->month = true;
        return $this;
    }

    public function forYear(): self
    {
        $this->year = true;
        return $this;
    }

    public function onlyCache(): self
    {
        $this->cache = true;
        return $this;
    }

    public function onlyHold(): self
    {
        $this->hold = true;
        return $this;
    }

    public function findAll(): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        //$dbal->select('id');
        //$dbal->from(ClasssName::class, 'aliace');

        return $dbal
            // ->enableCache('Namespace', 3600)
            ->fetchAllAssociative();
    }
}