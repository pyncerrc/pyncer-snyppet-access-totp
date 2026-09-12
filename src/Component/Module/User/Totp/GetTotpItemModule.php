<?php
namespace Pyncer\Snyppet\Access\Component\Module\User\Totp;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\App\Identifier as ID;
use Pyncer\Component\Module\AbstractModule;
use Pyncer\Data\Model\ModelInterface;
use Pyncer\Http\Message\JsonResponse;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;
use Pyncer\Snyppet\Access\Table\User\Totp\TotpMapper;

class GetTotpItemModule extends AbstractModule
{
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
        $mapper = new TotpMapper($connection);
        $model = $mapper->selectByUserId($userId);

        if ($model === null) {
            return new Response(
                Status::CLIENT_ERROR_404_NOT_FOUND
            );
        }

        return new JsonResponse(
            status: Status::SUCCESS_200_OK,
            body: $this->getResponseItemData($model),
        );
    }

    protected function getResponseItemData(ModelInterface $model): array
    {
        return [
            'method' => $model->getMethod(),
            'enabled' => $model->getEnabled(),
        ];
    }
}
