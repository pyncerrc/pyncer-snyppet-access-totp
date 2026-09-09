<?php
namespace Pyncer\Snyppet\Access\Component\Module\Token\Totp;

use OTPHP\TOTP;
use Pyncer\Snyppet\Access\Component\Module\Token\PatchTokenItemModule as PyncerPatchTokenItemModule;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;
use Pyncer\Snyppet\Access\Totp\TotpMethod;

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
        $mapper = new TotpMapper($connection);
        $model = $mapper->selectByUserId($model->getUserId());

        if ($model === null || !$model->getEnabled()) {
            $errors = [
                'general' => 'disabled'
            ];

            return $errors;
        }

        $totp = TOTP::createFromSecret($model->getSecret());

        if ($model->getMethod() === TotpMethod::APP) {
            $totp->setPeriod(PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD);
        } elseif ($model->getMethod() === TotpMethod::EMAIL) {
            $totp->setPeriod(PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD);
        } elseif ($model->getMethod() === TotpMethod::PHONE) {
            $totp->setPeriod(PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD);
        }

        $code = $this->parsedBody->getString('code', null);

        if ($code === null) {
            $errors = [
                'code' => 'required'
            ];

            return $errors;
        }

        if (!$totp->verify($code, null, 1)) {
            $errors = [
                'code' => 'invalid'
            ];

            return $errors;
        }

        $model->setScheme(parent::getScheme() ?? PYNCER_ACCESS_DEFAULT_SCHEME);

        return parent::updateItem($model);
    }
}
