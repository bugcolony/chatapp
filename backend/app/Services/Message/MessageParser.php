<?php

namespace App\Services\Message;

use App\Models\Member;
use Illuminate\Support\Collection;

class MessageParser
{
    public const string MENTION_REGEXP = '/\[@([1-9]\d{0,17})]/';

    public function __construct(private readonly string $content) {}

    public function raw(): string
    {
        return $this->content;
    }

    public function parseMentionsWithContext(ParserContext $ctx): Collection
    {
        $matches = $this->parseMentionTokens();
        $mentions = collect();

        if (count($matches) === 2 && count($matches[1]) > 0) {
            Member::query()
                ->where('members.server_id', $ctx->serverId)
                ->whereIn('members.user_id', $matches[1])
                ->get()
                ->each(function ($member) use (&$mentions) {
                    $mentions->push([
                        'user_id' => $member->user_id,
                        'fallback_name' => $member->nickname,
                    ]);
                });
        }

        return $mentions;
    }

    private function parseMentionTokens(): array
    {
        $matches = [];

        preg_match_all(self::MENTION_REGEXP, $this->content, $matches);

        return $matches;
    }
}
