<?php
namespace Pyncer\Snyppet\Access\Table\User\Totp;

use Pyncer\Data\Model\AbstractModel;
use Pyncer\Snyppet\Access\Totp\TotpMethod;

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

    public function getMethod(): ?TotpMethod
    {
        $value = $this->get('method');

        if ($value === null) {
            return null;
        }

        return TotpMethod::from($value);
    }
    public function setMethod(null|string|TotpMethod $value): static
    {
        if ($value instanceof TotpMethod) {
            $value = $value->value;
        }

        $this->set('method', $value);
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
            'method' => 'app',
            'secret' => '',
            'enabled' => false,
        ];
    }
}
