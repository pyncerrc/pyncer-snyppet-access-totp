<?php
namespace Pyncer\Snyppet\Access\Component\Module\User\Totp;

use OTPHP\TOTP;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Model\ModelInterface;
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
use const Pyncer\Snyppet\Access\Totp\ISSUER as PYNCER_ACCESS_TOTP_ISSUER;

use const Pyncer\Snyppet\Access\Totp\METHOD_APP_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_APP_ENABLED;
use const Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_EMAIL_ENABLED;
use const Pyncer\Snyppet\Access\Totp\METHOD_PHONE_ENABLED AS PYNCER_ACCESS_TOTP_METHOD_PHONE_ENABLED;

use const Pyncer\Snyppet\Access\Totp\METHOD_APP_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD;
use const Pyncer\Snyppet\Access\Totp\METHOD_EMAIL_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD;
use const Pyncer\Snyppet\Access\Totp\METHOD_PHONE_PERIOD AS PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD;

class PutTotpItemModule extends AbstractModule
{
    protected bool $confirmPassword = false;
    protected ?LoginMethod $loginMethod = null;
    protected bool $confirmAppCode = false;
    protected ?string $issuer = null;

    public function getConfirmPassword(): bool
    {
        return $this->confirmPassword;
    }
    public function setConfirmPassword(bool $value): static
    {
        $this->confirmPassword = $value;
        return $this;
    }

    public function getConfirmAppCode(): bool
    {
        return $this->confirmAppCode;
    }
    public function setConfirmAppCode(bool $value): static
    {
        $this->confirmAppCode = $value;
        return $this;
    }

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
        $userId = $this->getUserId();

        if ($userId === 0) {
            return new Response(
                Status::CLIENT_ERROR_403_FORBIDDEN
            );
        }

        $connection = $this->get(ID::DATABASE);

        $enabled = $this->parsedBody->getBool('enabled');
        $insert = false;

        $mapper = new TotpMapper($connection);
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

        if ($this->getConfirmPassword()) {
            $userMapper = new UserMapper($connection);
            $userModel = $userMapper->selectById($userId);

            $password = $this->parsedBody->getString('password_old', null);

            if ($password === null) {
                $errors['password_old'] = 'required';
            } elseif (!password_verify($password, $userModel->getPassword())) {
                $errors['password_old'] = 'mismatch';
            }
        }

        $method = $this->parsedBody->getString('method', 'app');
        $method = TotpMethod::tryFrom($method);

        if ($method === null) {
            $errors['method'] = 'invalid';
        } elseif ($method === TotpMethod::APP &&
            !PYNCER_ACCESS_TOTP_METHOD_APP_ENABLED
        ) {
            $errors['method'] = 'invalid';
        } elseif ($method === TotpMethod::EMAIL &&
            !PYNCER_ACCESS_TOTP_METHOD_EMAIL_ENABLED
        ) {
            $errors['method'] = 'invalid';
        } elseif ($method === TotpMethod::PHONE &&
            !PYNCER_ACCESS_TOTP_METHOD_PHONE_ENABLED
        ) {
            $errors['method'] = 'invalid';
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
            $regenerate = true;
        }

        $model->setMethod($method);

        if ($enabled &&
            !$regenerate &&
            $method === TotpMethod::APP &&
            $this->getConfirmAppCode()
        ) {
            $code = $this->parsedBody->getString('code', null);
            if ($code === null) {
                $enabled = false;
            } else {
                $totp = TOTP::createFromSecret($model->getSecret());

                $period = match ($method) {
                    TotpMethod::APP => PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD,
                    TotpMethod::EMAIL => PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD,
                    TotpMethod::PHONE => PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD,
                };

                $totp->setPeriod($period);

                if (!$totp->verify($code, null, 5)) {
                    $errors['code'] = 'invalid';

                    return new JsonResponse(
                        Status::CLIENT_ERROR_422_UNPROCESSABLE_ENTITY,
                        ['errors' => $errors]
                    );
                }
            }
        }

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
                status: Status::SUCCESS_201_CREATED,
                body: $body,
            );
        } else {
            return new JsonResponse(
                status: Status::SUCCESS_200_OK,
                body: $body,
            );
        }
    }

    protected function getResponseItemData(ModelInterface $model): array
    {
        $data = [
            'method' => $model->getMethod(),
            'enabled' => $model->getEnabled(),
        ];

        if ($model->getMethod() == TotpMethod::APP) {
            $totp = TOTP::createFromSecret($model->getSecret());

            $period = match ($model->getMethod()) {
                TotpMethod::APP => PYNCER_ACCESS_TOTP_METHOD_APP_PERIOD,
                TotpMethod::EMAIL => PYNCER_ACCESS_TOTP_METHOD_EMAIL_PERIOD,
                TotpMethod::PHONE => PYNCER_ACCESS_TOTP_METHOD_PHONE_PERIOD,
            };

            $totp->setPeriod($period);

            $loginMethod = $this->getLoginMethod();

            $connection = $this->get(ID::DATABASE);
            $userMapper = new UserMapper($connection);
            $userModel = $userMapper->selectById($model->getUserId());

            if ($userModel !== null) {
                $label = match($loginMethod) {
                    LoginMethod::EMAIL => $userModel->getEmail(),
                    LoginMethod::PHONE => $userModel->getPhone(),
                    LoginMethod::USERNAME => $userModel->getUsername(),
                    default => null,
                };

                if ($label !== null) {
                    $totp->setLabel($label);
                }
            }

            $issuer = $this->getIssuer() ?? PYNCER_ACCESS_TOTP_ISSUER;

            if ($issuer !== null) {
                $totp->setIssuer($issuer);
            }

            $data['secret'] = $model->getSecret();
            $data['provisioning_uri'] = $totp->getProvisioningUri();
        }

        return $data;
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
            $connection = $this->get(ID::DATABASE);
            $mapper = new TotpMapper($connection);
            $mapper->replace($model);
        } catch (QueryException $e) {
            $errors['general'] = $error;
        }

        return $errors;
    }
}
