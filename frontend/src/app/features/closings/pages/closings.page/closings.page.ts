import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MonthlyClosingDto } from '../../../../core/models/closing.models';
import { MONTH_LABELS } from '../../../../shared/utils/date.util';

@Component({
  selector: 'app-closings-page',
  imports: [RouterLink, DecimalPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './closings.page.html',
  styleUrl: './closings.page.scss',
})
export class ClosingsPage {
  readonly closingsData = input.required<MonthlyClosingDto[]>();

  private readonly route = inject(ActivatedRoute);
  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly closings = signal<MonthlyClosingDto[]>([]);

  constructor() {
    effect(() => this.closings.set(this.closingsData()), { allowSignalWrites: true });
  }

  monthLabel(closing: MonthlyClosingDto): string {
    return `${MONTH_LABELS[closing.month - 1]} ${closing.year}`;
  }
}
