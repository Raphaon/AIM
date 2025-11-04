import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { Observable, map, shareReplay } from 'rxjs';
import { Permission, Role, RolePayload } from '../../../core/models/iam.models';
import { PermissionService } from '../../../core/services/permission.service';

export interface RoleFormData {
  role?: Role;
}

@Component({
  selector: 'app-role-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatButtonModule
  ],
  template: `
    <h2 mat-dialog-title>{{ data.role ? 'Modifier le rôle' : 'Créer un rôle' }}</h2>
    <mat-dialog-content>
      <form [formGroup]="form" class="form-grid">
        <mat-form-field appearance="outline">
          <mat-label>Nom</mat-label>
          <input matInput formControlName="name" required />
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput formControlName="description"></textarea>
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Permissions</mat-label>
          <mat-select formControlName="permissions" multiple>
            <mat-option *ngFor="let permission of permissions$ | async" [value]="permission.name">
              {{ permission.name }}
            </mat-option>
          </mat-select>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-stroked-button mat-dialog-close>Annuler</button>
      <button mat-flat-button color="primary" (click)="submit()" [disabled]="form.invalid">Sauvegarder</button>
    </mat-dialog-actions>
  `,
  styles: [
    `
      .form-grid {
        display: grid;
        gap: 16px;
        width: 460px;
        max-width: 100%;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class RoleFormComponent {
  protected readonly form: FormGroup;
  protected readonly permissions$: Observable<Permission[]>;

  constructor(
    private readonly dialogRef: MatDialogRef<RoleFormComponent, RolePayload>,
    @Inject(MAT_DIALOG_DATA) public readonly data: RoleFormData,
    private readonly permissionService: PermissionService,
    fb: FormBuilder
  ) {
    this.form = fb.group({
      name: [data.role?.name ?? '', Validators.required],
      description: [data.role?.description ?? ''],
      permissions: [data.role?.permissions?.map(permission => permission.name) ?? []]
    });

    this.permissions$ = this.permissionService
      .listPermissions()
      .pipe(map(response => response.data), shareReplay(1));
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const payload: RolePayload = {
      name: this.form.value.name,
      description: this.form.value.description,
      permissions: this.form.value.permissions
    };

    this.dialogRef.close(payload);
  }
}
