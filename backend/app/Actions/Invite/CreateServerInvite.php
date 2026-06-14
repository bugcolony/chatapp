<?php

namespace App\Actions\Invite;

use App\Data\Invite\CreateInviteData;
use App\Models\Server;
use App\Models\ServerInvite;
use Exception;

class CreateServerInvite
{
    public const int CODE_LENGTH = 12;

    public const int MAX_ATTEMPTS = 3;

    public const int UNIQUE_CONSTRAINT_CODE = 23505;

    /**
     * @throws Exception
     */
    public function execute(Server $server, CreateInviteData $inviteData): ?ServerInvite
    {
        $code = str()->random(self::CODE_LENGTH);
        $existingInvite = $server->invites()->valid()->first();

        if ($existingInvite) {
            return $existingInvite;
        }

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            try {
                return $server->invites()->create([
                    'code' => $code,
                    'created_by' => $inviteData->userId,
                    'max_uses' => $inviteData->maxUses,
                    'expires_at' => $inviteData->expiresAt,
                ]);
            } catch (Exception $e) {
                if ($e->getCode() !== self::UNIQUE_CONSTRAINT_CODE) {
                    throw $e;
                }
            }
        }

        return null;
    }
}
