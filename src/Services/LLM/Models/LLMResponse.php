<?php

namespace XAKEPEHOK\Lokilizer\Services\LLM\Models;

use XAKEPEHOK\Lokilizer\Models\LLM\LLMPricing;

readonly class LLMResponse
{
    public function __construct(
        public string  $text,
        public LLMPricing $pricing,
        public int     $inputTokens,
        public int     $outputTokens,
        public mixed   $parsedValue = null,
    )
    {
    }

    /**
     * @return array|null
     */
    public function getAsJson(): ?array
    {
        $text = $this->text;
        // 1. Попытка найти JSON, обернутый в блок ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{.*\}|\[.*\])\s*```/is', $text, $matches)) {
            $jsonCandidate = trim($matches[1]);
            $decoded = json_decode($jsonCandidate, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 2. Поиск первого символа { или [, с которого может начинаться JSON
        $posObj = strpos($text, '{');
        $posArr = strpos($text, '[');
        if ($posObj === false && $posArr === false) {
            return null; // JSON не найден
        }
        if ($posObj === false || ($posArr !== false && $posArr < $posObj)) {
            $startPos = $posArr;
            $opening = '[';
            $closing = ']';
        } else {
            $startPos = $posObj;
            $opening = '{';
            $closing = '}';
        }

        // 3. Поиск корректного JSON с учетом вложенности и строк
        $inString = false;
        $stringChar = '';
        $escape = false;
        $depth = 0;
        $length = strlen($text);

        for ($i = $startPos; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = '';
                }
            } else {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $opening) {
                    $depth++;
                } elseif ($char === $closing) {
                    $depth--;
                    if ($depth === 0) {
                        $jsonCandidate = substr($text, $startPos, $i - $startPos + 1);
                        $decoded = json_decode(trim($jsonCandidate), true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            return $decoded;
                        }
                        break;
                    }
                }
            }
        }
        return null;
    }

    public function calcPrice(): float
    {
        $input = $this->inputTokens / 1000000 * $this->pricing->inputPer1M;
        $output = $this->outputTokens / 1000000 * $this->pricing->outputPer1M;
        return $input + $output;
    }

}