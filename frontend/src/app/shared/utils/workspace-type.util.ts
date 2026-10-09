import { WorkspaceType } from '../../core/models/workspace.models';

export const WORKSPACE_TYPE_LABELS: Record<WorkspaceType, string> = {
  individual: 'Individual',
  shared_joint: 'Compartido conjunto',
  shared_separate: 'Compartido separado',
  shared_settlement: 'Gastos compartidos',
};

export const WORKSPACE_TYPE_ICONS: Record<WorkspaceType, string> = {
  individual: 'person',
  shared_joint: 'diversity_3',
  shared_separate: 'call_split',
  shared_settlement: 'receipt_long',
};
