<?php
namespace Pyncer\Snyppet\Access\Component\Module\Token\Totp;

use OTPHP\TOTP;
use Pyncer\App\Identifier as ID;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Snyppet\Access\Component\Module\Token\PatchTokenItemModule as PyncerPatchTokenItemModule;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;
use Pyncer\Snyppet\Access\Totp\TotpMethod;

use const Pyncer\Snyppet\Access\DEFAULT_SCHEME as PYNCER_ACCESS_DEFAULT_SCHEME;
use const Pyncer\Snyppet\Access\Totp\SCHEME as PYNCER_ACCESS_TOTP_SCHEME;
use const Pyncer\Snyppet\Access\Totp\METHOD_APP_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD;
use const Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD;
use const Pyncer\Snyppet\Access\Totp\METHOD_PHONE_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD;

class PatchTokenItemModule extends PyncerPatchTokenItemModule
{
    public function getScheme(): ?string
    {
        // ForgeMapperQuery will use this value.
        return PYNCER_ACCESS_TOTP_SCHEME;
    }

    protected function updateItem(ModelInterface $model): array
    {
        $connection = $this->get(ID::DATABASE);
        $mapper = new TotpMapper($connection);
        $totpModel = $mapper->selectByUserId($model->getUserId());

        if ($totpModel === null || !$totpModel->getEnabled()) {
            $errors = [
                'general' => 'disabled'
            ];

            return $errors;
        }

        $totp = TOTP::createFromSecret($totpModel->getSecret());

        $period = match ($totpModel->getMethod()) {
            TotpMethod::APP => PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD,
            TotpMethod::EMAIL => PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD,
            TotpMethod::PHONE => PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD,
        };

        $totp->setPeriod($period);

        $code = $this->parsedBody->getString('code', null);

        if ($code === null) {
            $errors = [
                'code' => 'required'
            ];

            return $errors;
        }

        if (!$totp->verify($code, null, 5)) {
            $errors = [
                'code' => 'invalid'
            ];

            return $errors;
        }

        $model->setScheme(parent::getScheme() ?? PYNCER_ACCESS_DEFAULT_SCHEME);

        return parent::updateItem($model);
    }
}
