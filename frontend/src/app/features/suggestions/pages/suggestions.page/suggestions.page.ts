import { DatePipe, DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { SmartSuggestionDto } from '../../../../core/models/suggestion.models';
import { ToastService } from '../../../../core/services/toast.service';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { AcceptSuggestionDialog, AcceptSuggestionDialogData } from '../../components/accept-suggestion-dialog/accept-suggestion-dialog';
import { AcceptSuggestionResult, SuggestionsResource } from '../../suggestions.resource';

@Component({
  selector: 'app-suggestions-page',
  imports: [MatButtonModule, DecimalPipe, DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './suggestions.page.html',
  styleUrl: './suggestions.page.scss',
})
export class SuggestionsPage {
  readonly suggestionsData = input.required<SmartSuggestionDto[]>();

  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(SuggestionsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly suggestions = signal<SmartSuggestionDto[]>([]);

  constructor() {
    effect(() => this.suggestions.set(this.suggestionsData()), { allowSignalWrites: true });
  }

  acceptSuggestion(suggestion: SmartSuggestionDto): void {
    const ref = this.dialog.open<AcceptSuggestionDialog, AcceptSuggestionDialogData, AcceptSuggestionResult | undefined>(
      AcceptSuggestionDialog,
      { data: { workspaceId: this.workspaceId, suggestion }, width: '480px' },
    );

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(`Servicio "${result.service.name}" creado desde la sugerencia.`);
        this.reload();
      }
    });
  }

  dismissSuggestion(suggestion: SmartSuggestionDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Descartar sugerencia',
          message: `¿Descartar "${suggestion.description}"? No se va a volver a proponer.`,
          confirmLabel: 'Descartar',
          cancelLabel: 'Volver',
          icon: 'lightbulb',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }

        this.resource.dismiss(this.workspaceId, suggestion.id).subscribe({
          next: () => {
            this.toast.success('Sugerencia descartada.');
            this.reload();
          },
        });
      });
  }

  private reload(): void {
    this.resource.list(this.workspaceId, 'pending').subscribe((suggestions) => this.suggestions.set(suggestions));
  }
}
