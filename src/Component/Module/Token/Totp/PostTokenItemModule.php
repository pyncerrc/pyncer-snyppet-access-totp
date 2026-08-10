<?php
namespace Pyncer\Snyppet\Access\Component\Module\Token\Totp;

use Pyncer\Snyppet\Access\Component\Module\Token\PostTokenItemModule as PyncerPostTokenItemModule;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;

use const Pyncer\Snyppet\Access\TOTP_SCHEME as PYNCER_ACCESS_TOTP_SCHEME;

class PostTokenItemModule extends PyncerPostTokenItemModule
{
    protected bool $isTotp = false;

    protected function login(AccessManager $accessManager): ?bool
    {
        $result = parent::login($accessManager);

        if ($result === true) {
            $connection = $this->get(ID::DATABASE);

            $mapper = new TotpMapper($connection);
            $model = $mapper->selectByUserId($accessManager->getUserId());

            if ($model !== null && $model->getEnabled()) {
                $this->isTotp = true;
            }
        }

        return $result;
    }

    protected function insertItem(ModelInterface $model): array
    {
        if ($this->isTotp) {
            $model->setScheme(PYNCER_ACCESS_TOTP_SCHEME);
        }

        return parent::insertItem($model);
    }

    protected function getResponseUserData(ModelInterface $userModel): array
    {
        $data = parent::getResponseUserData($userModel);
        $data['totp'] = $this->isTotp;

        return $data;
    }
}
