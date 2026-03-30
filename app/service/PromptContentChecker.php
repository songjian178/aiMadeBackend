<?php
declare(strict_types=1);

namespace app\service;

/**
 * 生成类 prompt 文本合规校验（关键词子串匹配，可配置）。
 */
class PromptContentChecker
{
    /**
     * @return string|null 违规时返回提示文案，通过返回 null
     */
    public static function validatePrompt(string $prompt): ?string
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return null;
        }

        $keywords = (array)config('prompt_sensitive.keywords', []);
        $message = (string)config('prompt_sensitive.message', '内容包含违规信息，请修改提示词后重试');
        if ($keywords === []) {
            return null;
        }

        $normalized = self::normalizeForMatch($prompt);
        $needles = array_values(array_filter(array_map(
            static fn($word) => is_string($word) ? self::normalizeForMatch($word) : '',
            $keywords
        ), static fn(string $word) => $word !== ''));

        if ($needles === []) {
            return null;
        }

        // 一次性批量替换关键词，替换前后不同即命中敏感词
        $replaced = str_ireplace($needles, '', $normalized);
        if ($replaced !== $normalized) {
            return $message;
        }

        return null;
    }

    private static function normalizeForMatch(string $text): string
    {
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        return $text;
    }
}
