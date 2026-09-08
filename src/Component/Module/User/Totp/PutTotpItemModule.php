<?php
namespace Pyncer\Snyppet\Access\Component\Module\User\Totp;

use OTPHP\TOTP;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Database\Exception\QueryException;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;
use Pyncer\Snyppet\Access\Table\User\UserMapper;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpValidator;
use Pyncer\Snyppet\Access\User\LoginMethod;
use Pyncer\Snyppet\Access\Totp\TotpMethod;

use const Pyncer\Snyppet\Access\LOGIN_METHOD as PYNCER_ACCESS_LOGIN_METHOD;
use const Pyncer\Snyppet\Access\TOTP_ISSUER as PYNCER_ACCESS_TOTP_ISSUER;

use const Pyncer\Snyppet\Access\TOTP_METHOD_APP_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_APP_ENABLED;
use const Pyncer\Snyppet\Access\TOTP_METHOD_EMAIL_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_EMAIL_ENABLED;
use const Pyncer\Snyppet\Access\TOTP_METHOD_PHONE_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_PHONE_ENABLED;

class PutTotpItemModule extends AbstractModule
{
    protected ?LoginMethod $loginMethod = null;
    protected ?string $issuer = null;

    public function getLoginMethod(): LoginMethod
    {
        if ($this->loginMethod !== null) {
            return $this->loginMethod;
        }

        $loginMethod = PYNCER_ACCESS_LOGIN_METHOD;

        $snyppetManager = $this->get(ID::SNYPPET);
        if ($snyppetManager->has('config')) {
            $config = $this->get(ID::config());

            $loginMethod = $config->getString(
                'user_login_method',
                $loginMethod->value
            );
            $loginMethod = LoginMethod::from($loginMethod);
        }

        return $loginMethod;
    }
    public function setLoginMethod(?LoginMethod $value): static
    {
        $this->loginMethod = $value;

        return $this;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }
    public function setIssuer(?string $value): static
    {
        if ($value === '') {
            $value = null;
        }

        $this->issuer = $value;
        return $this;
    }

    protected function getUserId(): int
    {
        if (!$this->has(ID::ACCESS)) {
            return 0;
        }

        $access = $this->get(ID::ACCESS);

        if ($access->isGuest()) {
            return 0;
        }

        return $access->getUserId();
    }

    protected function getPrimaryResponse(): PsrResponseInterface
    {
        $connection = $this->get(ID::DATABASE);

        $userId = $this->getUserId();

        if ($userId === 0) {
            return new Response(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $enabled = $this->parsedBody->getBool('enabled');
        $insert = false;

        $mapper new TotpMapper($connection);
        $model = $mapper->selectByUserId($userId);
        if ($model === null) {
            if (!$enabled) {
                return new Response(
                    Status::SUCCESS_204_NO_CONTENT,
                );
            }

            $insert = true;

            $model = $mapper->forgeModel([
                'user_id' => $userId,
            ]);
        }

        $errors = [];

        $method = $this->parsedBody->getString('method', 'app');
        $method = TotpMethod::tryFrom($method);

        if ($method === null) {
            $errors['method'] = 'invalid'
        } elseif ($method === TotpMethod::APP &&
            !PYNCER_ACCESS_TOTP_METHOD_APP_ENABLED
        ) {
            $errors['method'] = 'invalid'
        } elseif ($method === TotpMethod::EMAIL &&
            !PYNCER_ACCESS_TOTP_METHOD_EMAIL_ENABLED
        ) {
            $errors['method'] = 'invalid'
        } elseif ($method === TotpMethod::PHONE &&
            !PYNCER_ACCESS_TOTP_METHOD_PHONE_ENABLED
        ) {
            $errors['method'] = 'invalid'
        }

        if ($errors) {
            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        $regenerate = $this->parsedBody->getBool('regenerate');

        if ($model->getSecret() === '' || $regenerate) {
            $totp = TOTP::create();

            $model->setSecret($totp->getSecret());
        }

        $model->setMethod($method);

        $model->setEnabled($enabled);

        $errors = $this->replaceItem($model);

        if ($errors) {
            if (($errors['general'] ?? null) === 'insert' ||
                ($errors['general'] ?? null) === 'update'
            ) {
                return new Response(
                    Status::SERVER_ERROR_500_INTERNAL_SERVER_ERROR,
                );
            }

            return new JsonResponse(
                Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                ['errors' => $errors]
            );
        }

        $body = $this->getResponseItemData($model);

        if ($insert) {
            return new JsonResponse(
                Status::SUCCESS_201_CREATED,
                $body,
            );
        } else {
            return new JsonResponse(
                Status::SUCCESS_200_OK,
                $body,
            );
        }
    }

    protected function getResponseItemData(ModelInterface $model): array
    {
        $totp = TOTP::createFromSecret($model->getSecret());

        $loginMethod = $this->getLoginMethod();

        $userMapper = new UserMapper($connection);
        $userModel = $userMapper->selectById($model->getUserId());

        if ($userModel !== null) {
            $label = match($loginMethod) {
                LoginMethod::EMAIL => $userModel->getEmail(),
                LoginMethod::PHONE => $userModel->getPhone(),
                LoginMethod::USERNAME => $userModel->getUsername(),
                default => null,
            }

            if ($label !== null) {
                $totp->setLabel($label);
            }
        }

        $issuer = $this->getIssuer() ?? PYNCER_ACCESS_TOTP_ISSUER;

        if ($issuer !== null) {
            $totp->setIssuer($issuer);
        }

        return [
            'provisioning_uri' => $totp->getProvisioningUri()
        ];
    }

    protected function replaceItem(ModelInterface $model): array
    {
        $errors = [];

        if ($model->getId()) {
            $error = 'update';
        } else {
            $error = 'insert';
        }

        try {
            $mapper new TotpMapper($connection);
            $mapper->replace($model);
        } catch (QueryException) {
            $errors['general'] = $error;
        }

        return $errors;
    }
}
