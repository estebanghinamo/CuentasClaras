import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { catchError, forkJoin, map, of } from 'rxjs';
import { ApiError } from '../../../../core/models/api.models';
import { WorkspaceResource } from '../../../../core/resources/workspace.resource';
import { Spinner } from '../../../../shared/components/spinner/spinner';

export interface QuickInviteDialogData {
  workspaceId: number;
  workspaceName: string;
}

interface InviteResult {
  email: string;
  success: boolean;
  message?: string;
}

@Component({
  selector: 'app-quick-invite-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './quick-invite-dialog.html',
  styleUrl: './quick-invite-dialog.scss',
})
export class QuickInviteDialog {
  protected readonly data = inject<QuickInviteDialogData>(MAT_DIALOG_DATA);
  private readonly fb = inject(FormBuilder);
  private readonly workspaces = inject(WorkspaceResource);

  readonly sending = signal(false);
  readonly results = signal<InviteResult[] | null>(null);

  readonly form = this.fb.group({
    emails: this.fb.array([this.newEmailControl()]),
  });

  get emails() {
    return this.form.controls.emails;
  }

  private newEmailControl() {
    return this.fb.nonNullable.control('', [Validators.required, Validators.email]);
  }

  addEmail(): void {
    this.emails.push(this.newEmailControl());
  }

  removeEmail(index: number): void {
    this.emails.removeAt(index);
  }

  submit(): void {
    if (this.sending()) {
      return;
    }

    const uniqueEmails = [...new Set(this.emails.controls.filter((c) => c.valid).map((c) => c.value.trim().toLowerCase()))];

    if (uniqueEmails.length === 0) {
      this.emails.controls.forEach((c) => c.markAsTouched());
      return;
    }

    this.sending.set(true);

    forkJoin(
      uniqueEmails.map((email) =>
        this.workspaces.createInvitation(this.data.workspaceId, { email }).pipe(
          map(() => ({ email, success: true }) as InviteResult),
          catchError((error: ApiError) =>
            of<InviteResult>({ email, success: false, message: error?.error?.message ?? 'No se pudo invitar.' }),
          ),
        ),
      ),
    ).subscribe((items) => {
      this.sending.set(false);
      this.results.set(items);
    });
  }
}
