<?php
namespace Pyncer\Snyppet\Access\Table\User\Totp;

use Pyncer\Snyppet\Access\Table\User\Totp\TotpModel;
use Pyncer\Data\Mapper\AbstractMapper;
use Pyncer\Data\Model\ModelInterface;

class TotpMapper extends AbstractMapper
{
    public function getTable(): string
    {
        return 'user__totp';
    }

    public function forgeModel(iterable $data = []): ModelInterface
    {
        return new TotpModel($data);
    }

    public function isValidModel(ModelInterface $model): bool
    {
        return ($model instanceof TotpModel);
    }

    public function selectByUserId(
        int $userId,
    ): ?ModelInterface
    {
        return $this->selectByColumns(
            ['user_id' => $userId],
        );
    }
}
