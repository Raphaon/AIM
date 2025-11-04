import { ChangeDetectionStrategy, Component, Inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { CommonModule } from '@angular/common';
import { RoleService } from '../../../core/services/role.service';
import { PermissionService } from '../../../core/services/permission.service';
import { Permission, Role, User, UserPayload } from '../../../core/models/iam.models';
import { Observable, map, shareReplay } from 'rxjs';

export interface UserFormData {
  user?: User;
}

@Component({
  selector: 'app-user-form',
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
    <h2 mat-dialog-title>{{ data.user ? 'Modifier l\'utilisateur' : 'Créer un utilisateur' }}</h2>
    <mat-dialog-content>
      <form [formGroup]="form" class="form-grid">
        <mat-form-field appearance="outline">
          <mat-label>Nom</mat-label>
          <input matInput formControlName="name" required />
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Email</mat-label>
          <input matInput type="email" formControlName="email" required />
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Téléphone</mat-label>
          <input matInput formControlName="phone" />
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Statut</mat-label>
          <mat-select formControlName="status">
            <mat-option value="active">Actif</mat-option>
            <mat-option value="suspended">Suspendu</mat-option>
          </mat-select>
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Mot de passe</mat-label>
          <input matInput type="password" formControlName="password" [required]="!data.user" />
        </mat-form-field>

        <mat-form-field appearance="outline">
          <mat-label>Rôles</mat-label>
          <mat-select formControlName="roles" multiple>
            <mat-option *ngFor="let role of roles$ | async" [value]="role.name">{{ role.name }}</mat-option>
          </mat-select>
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        width: 540px;
        max-width: 100%;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class UserFormComponent implements OnInit {
  protected readonly form: FormGroup;
  protected readonly roles$: Observable<Role[]>;
  protected readonly permissions$: Observable<Permission[]>;

  constructor(
    private readonly dialogRef: MatDialogRef<UserFormComponent, UserPayload>,
    @Inject(MAT_DIALOG_DATA) public readonly data: UserFormData,
    private readonly roleService: RoleService,
    private readonly permissionService: PermissionService,
    fb: FormBuilder
  ) {
    this.form = fb.group({
      name: [data.user?.name ?? '', Validators.required],
      email: [data.user?.email ?? '', [Validators.required, Validators.email]],
      phone: [data.user?.phone ?? ''],
      status: [data.user?.status ?? 'active', Validators.required],
      password: [''],
      roles: [data.user?.roles?.map(role => role.name) ?? []],
      permissions: [data.user?.permissions?.map(permission => permission.name) ?? []]
    });

    this.roles$ = this.roleService
      .listRoles()
      .pipe(map(response => response.data), shareReplay(1));

    this.permissions$ = this.permissionService
      .listPermissions()
      .pipe(map(response => response.data), shareReplay(1));
  }

  ngOnInit(): void {
    if (!this.data.user) {
      this.form.get('password')?.addValidators(Validators.required);
    }
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const payload: UserPayload = {
      ...this.form.value,
      password: this.form.value.password || undefined
    };

    this.dialogRef.close(payload);
  }
}
