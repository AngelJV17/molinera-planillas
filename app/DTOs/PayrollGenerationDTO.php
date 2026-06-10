<?php

namespace App\DTOs;

final readonly class PayrollGenerationDTO
{
    public function __construct(
        public int $periodYear,
        public int $periodMonth,
        public ?int $generatedBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            periodYear: (int) $data['period_year'],
            periodMonth: (int) $data['period_month'],
            generatedBy: isset($data['generated_by']) ? (int) $data['generated_by'] : null,
        );
    }
}
