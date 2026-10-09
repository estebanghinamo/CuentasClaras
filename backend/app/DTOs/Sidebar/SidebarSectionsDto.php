<?php

namespace App\DTOs\Sidebar;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SidebarSections',
    required: ['sections'],
    properties: [
        new OA\Property(
            property: 'sections',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['budgets', 'savings', 'goals', 'members', 'activity']),
            example: ['budgets', 'savings'],
        ),
    ],
    type: 'object',
)]
final readonly class SidebarSectionsDto
{
    /** @param string[] $sections */
    public function __construct(public array $sections)
    {
    }

    public static function fromArray(array $row): self
    {
        return new self(sections: array_values($row['sections'] ?? []));
    }

    public function toArray(): array
    {
        return ['sections' => $this->sections];
    }
}
