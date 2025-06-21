<?php

namespace XAKEPEHOK\Lokilizer\Models\LLM;

readonly class LLMPricing
{

    public function __construct(
        public float $inputPer1M,
        public float $outputPer1M
    )
    {
    }

}