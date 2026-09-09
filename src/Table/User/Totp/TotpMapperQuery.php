<?php
namespace Pyncer\Snyppet\Access\Table\User\Totp;

use Pyncer\Data\MapperQuery\AbstractRequestMapperQuery;

class TotpMapperQuery extends AbstractRequestMapperQuery
{
    protected function isValidFilter(
        string $left,
        mixed $right,
        string $operator,
    ): bool
    {
        if ($left === 'user_id' && is_int($right) && $operator === '=') {
            return true;
        }

        if ($left === 'method' && is_string($right) && $operator === '=') {
            return true;
        }

        return parent::isValidFilter($left, $right, $operator);
    }
}
