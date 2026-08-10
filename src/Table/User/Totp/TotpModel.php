<?php
namespace Pyncer\Snyppet\Access\Table\User\Totp;

use Pyncer\Data\Model\AbstractModel;

class TotpModel extends AbstractModel
{
    public function getUserId(): int
    {
        return $this->get('user_id');
    }
    public function setUserId(int $value): static
    {
        $this->set('user_id', $value);
        return $this;
    }

    public function getSecret(): string
    {
        return $this->get('secret');
    }
    public function setSecret(string $value): static
    {
        $this->set('secret', $value);
        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->get('enabled');
    }
    public function setEnabled(bool $value): static
    {
        $this->set('enabled', $value);
        return $this;
    }

    public static function getDefaultData(): array
    {
        return [
            'id' => 0,
            'user_id' => 0,
            'secret' => '',
            'enabled' => false,
        ];
    }
}
