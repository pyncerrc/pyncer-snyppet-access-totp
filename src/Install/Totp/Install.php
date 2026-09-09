<?php
namespace Pyncer\Snyppet\Access\Install\Totp;

use Pyncer\Database\Table\Column\IntSize;
use Pyncer\Database\Table\ReferentialAction;
use Pyncer\Snyppet\AbstractInstall;

class Install extends AbstractInstall
{
    /**
     * @inheritdoc
     */
    protected function safeInstall(): bool
    {
        $this->connection->createTable('user__totp')
            ->serial('id')
            ->int('user_id', IntSize::BIG)->index()
            ->enum('method', ['app', 'email', 'phone'])->default('app')->index()
            ->string('secret', 64)->index()
            ->bool('enabled')->default(false)->index()
            ->index('#unique', 'user_id')->unique()
            ->foreignKey(null, 'user_id')
                ->references('user', 'id')
                ->deleteAction(ReferentialAction::CASCADE)
                ->updateAction(ReferentialAction::CASCADE)
            ->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function safeUninstall(): bool
    {
        if ($this->connection->hasTable('user__totp')) {
            $this->connection->dropTable('user__totp');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getRequired(): array
    {
        return [
            'access' => '*'
        ];
    }
}
