<?php
namespace Pyncer\Snyppet\Access\Component\Module\Token\Totp;

use OTPHP\TOTP;
use Pyncer\App\Identifier as ID;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Snyppet\Access\Component\Module\Token\PostTokenItemModule as PyncerPostTokenItemModule;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpModel;
use Pyncer\Snyppet\Access\Table\Token\TokenModel;
use Pyncer\Snyppet\Access\Table\User\UserModel;
use Pyncer\Snyppet\Access\Totp\TotpMethod;
use Pyncer\Snyppet\Access\User\AccessManager;

use const Pyncer\Snyppet\Access\Totp\SCHEME as PYNCER_ACCESS_TOTP_SCHEME;
use const Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD;
use const Pyncer\Snyppet\Access\Totp\METHOD_PHONE_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD;

abstract class AbstractPostTokenItemModule extends PyncerPostTokenItemModule
{
    protected ?TotpModel $totpModel = null;

    protected function login(AccessManager $accessManager): ?bool
    {
        $result = parent::login($accessManager);

        if ($result === true) {
            $connection = $this->get(ID::DATABASE);

            $mapper = new TotpMapper($connection);
            $model = $mapper->selectByUserId($accessManager->getUserId());

            if ($model !== null && $model->getEnabled()) {
                $this->totpModel = $model;
            }
        }

        return $result;
    }

    protected function insertItem(ModelInterface $model): array
    {
        if ($this->totpModel !== null) {
            $model->setScheme(PYNCER_ACCESS_TOTP_SCHEME);
        }

        $errors = parent::insertItem($model);
        if ($errors) {
            return $errors;
        }

        if ($this->totpModel !== null &&
            $this->totpModel->getMethod() !== TotpMethod::APP
        ) {
            $userModel = $tokenModel->getSideModel('user');

            $totp = TOTP::createFromSecret($model->getSecret());

            if ($totpModel->getMethod() !== TotpMethod::PHONE) {
                $totp->setPeriod(PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD);

                if (!$this->sendTotpCode(
                    $totp->now(),
                    $userModel,
                    null,
                    $userModel->getPhone(),
                )) {
                    $errors = ['general' => 'send'];
                }
            } else {
                $totp->setPeriod(PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD);

                if (!$this->sendTotpCode(
                    $totp->now(),
                    $userModel,
                    $userModel->getEmail(),
                    null,
                )) {
                    $errors = ['general' => 'send'];
                }
            }
        }

        return $errors;
    }

    protected function getResponseItemData(TokenModel $tokenModel): array
    {
        $data = parent::getResponseItemData($tokenModel);
        $data['totp'] = (
            $this->totpModel !== null ?
            $this->totpModel->getMethod()->value :
            null
        );

        return $data;
    }

    abstract protected function sendTotpCode(
        string $code,
        UserModel $userModel,
        ?string $email,
        ?string $phone,
    ): bool;
}
