<?php

namespace App\Services\Bot;

use App\Models\BotLog;
use Throwable;

/**
 * Bot günlüğü. Panelde okunabilir olsun diye ayrı tabloda tutulur.
 *
 * Not: her engellenen haber için log yazılmaz — referans botta bu davranış
 * veritabanını şişirmişti. Toplu sonuçlar cron özetine (bot_runs) yazılır.
 */
class BotLogger
{
    public static function info(string $message, array $context = [], string $channel = 'system', ?int $sourceId = null): void
    {
        self::write('info', $channel, $message, $context, $sourceId);
    }

    public static function warning(string $message, array $context = [], string $channel = 'system', ?int $sourceId = null): void
    {
        self::write('warning', $channel, $message, $context, $sourceId);
    }

    public static function error(string $message, array $context = [], string $channel = 'system', ?int $sourceId = null): void
    {
        self::write('error', $channel, $message, $context, $sourceId);
    }

    public static function exception(Throwable $e, string $channel = 'system', ?int $sourceId = null): void
    {
        self::write('error', $channel, mb_substr($e->getMessage(), 0, 500), [
            'file' => basename($e->getFile()) . ':' . $e->getLine(),
        ], $sourceId);
    }

    private static function write(string $level, string $channel, string $message, array $context, ?int $sourceId): void
    {
        try {
            BotLog::create([
                'level' => $level,
                'channel' => $channel,
                'message' => mb_substr($message, 0, 500),
                'context' => $context ?: null,
                'source_id' => $sourceId,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Günlük yazılamıyorsa asıl işi durdurma
        }
    }

    /** Panelin şişmemesi için eski kayıtları siler */
    public static function prune(int $days = 30): int
    {
        return BotLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
