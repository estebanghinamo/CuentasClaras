export type OptionalSidebarSection = 'budgets' | 'savings' | 'goals' | 'members' | 'activity';

export interface SidebarSectionsDto {
  sections: OptionalSidebarSection[];
}

export interface SetSidebarSectionsRequest {
  sections: OptionalSidebarSection[];
}
