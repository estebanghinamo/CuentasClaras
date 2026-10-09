<?php

namespace App\DTOs\Finance;

final readonly class ExpenseFiltersDto
{
    /** @param int[] $categoryIds */
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public array $categoryIds = [],
        public ?int $userId = null,
        public ?string $paymentMethod = null,
        public ?string $amountMin = null,
        public ?string $amountMax = null,
        public ?string $search = null,
        public string $sort = 'date',
        public string $order = 'desc',
    ) {}

    public static function fromRequest(array $data): self
    {
        $categoryIds = [];
        if (!empty($data['category_ids'])) {
            $categoryIds = array_map('intval', $data['category_ids']);
        } elseif (!empty($data['category_id'])) {
            $categoryIds = [(int) $data['category_id']];
        }

        return new self(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            categoryIds: $categoryIds,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            paymentMethod: $data['payment_method'] ?? null,
            amountMin: isset($data['amount_min']) ? (string) $data['amount_min'] : null,
            amountMax: isset($data['amount_max']) ? (string) $data['amount_max'] : null,
            search: $data['search'] ?? null,
            sort: $data['sort'] ?? 'date',
            order: $data['order'] ?? 'desc',
        );
    }

    public function toPayload(int $workspaceId): array
    {
        return [
            'workspace_id' => $workspaceId,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'category_ids' => $this->categoryIds,
            'user_id' => $this->userId,
            'payment_method' => $this->paymentMethod,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'search' => $this->search,
            'sort' => $this->sort,
            'order' => $this->order,
        ];
    }
}
