<?php
namespace Pyncer\Snyppet\Access\Table\User\Totp;

use Pyncer\Data\Validation\AbstractValidator;
use Pyncer\Database\ConnectionInterface;
use Pyncer\Snyppet\Access\Table\User\UserMapper;
use Pyncer\Validation\Rule\BoolRule;
use Pyncer\Validation\Rule\EnumRule;
use Pyncer\Validation\Rule\IdRule;
use Pyncer\Validation\Rule\IntRule;
use Pyncer\Validation\Rule\RequiredRule;
use Pyncer\Validation\Rule\StringRule;

class TotpValidator extends AbstractValidator
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);

        $this->addRules(
            'user_id',
            new IntRule(
                minValue: 0,
                allowNull: true,
            ),
            new IdRule(
                mapper: new UserMapper($this->getConnection()),
            ),
        );

        $this->addRules(
            'method',
            new RequiredRule(),
            new EnumRule([
                'app', 'email', 'phone'
            ]),
        );

        $this->addRules(
            'secret',
            new StringRule(
                maxLength: 64,
                allowNull: true,
            ),
        );

        $this->addRules(
            'enabled',
            new BoolRule(),
        );
    }
}
