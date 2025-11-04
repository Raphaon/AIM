import { ChangeDetectionStrategy, Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatChipsModule } from '@angular/material/chips';
import { Role, RolePayload } from '../../../core/models/iam.models';
import { RoleService } from '../../../core/services/role.service';
import { RoleFormComponent, RoleFormData } from '../role-form/role-form.component';

@Component({
  selector: 'app-roles-list',
  standalone: true,
  imports: [
    CommonModule,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatDialogModule,
    MatChipsModule
  ],
  template: `
    <div class="page-container">
      <div class="header">
        <h2>Rôles</h2>
        <button mat-flat-button color="primary" (click)="openForm()">
          <mat-icon>add</mat-icon>
          Nouveau rôle
        </button>
      </div>

      <div class="table-wrapper">
        <table mat-table [dataSource]="dataSource">
          <ng-container matColumnDef="name">
            <th mat-header-cell *matHeaderCellDef>Nom</th>
            <td mat-cell *matCellDef="let role">{{ role.name }}</td>
          </ng-container>

          <ng-container matColumnDef="description">
            <th mat-header-cell *matHeaderCellDef>Description</th>
            <td mat-cell *matCellDef="let role">{{ role.description || '—' }}</td>
          </ng-container>

          <ng-container matColumnDef="permissions">
            <th mat-header-cell *matHeaderCellDef>Permissions</th>
            <td mat-cell *matCellDef="let role">
              <mat-chip-set>
                <mat-chip *ngFor="let permission of role.permissions">{{ permission.name }}</mat-chip>
              </mat-chip-set>
            </td>
          </ng-container>

          <ng-container matColumnDef="actions">
            <th mat-header-cell *matHeaderCellDef>Actions</th>
            <td mat-cell *matCellDef="let role">
              <button mat-icon-button color="primary" (click)="openForm(role)">
                <mat-icon>edit</mat-icon>
              </button>
              <button mat-icon-button color="warn" (click)="deleteRole(role)">
                <mat-icon>delete</mat-icon>
              </button>
            </td>
          </ng-container>

          <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
          <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
        </table>
      </div>
    </div>
  `,
  styles: [
    `
      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
      }

      .table-wrapper {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: auto;
      }

      table {
        width: 100%;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class RolesListComponent implements OnInit {
  private readonly roleService = inject(RoleService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  displayedColumns = ['name', 'description', 'permissions', 'actions'];
  dataSource = new MatTableDataSource<Role>([]);

  ngOnInit(): void {
    this.loadRoles();
  }

  loadRoles(): void {
    this.roleService.listRoles().subscribe({
      next: response => {
        this.dataSource.data = response.data;
      },
      error: () => {
        this.snackBar.open('Impossible de charger les rôles', 'Fermer', { duration: 4000 });
      }
    });
  }

  openForm(role?: Role): void {
    const dialogRef = this.dialog.open<RoleFormComponent, RoleFormData, RolePayload>(RoleFormComponent, {
      width: '520px',
      data: { role }
    });

    dialogRef.afterClosed().subscribe(payload => {
      if (!payload) {
        return;
      }

      const request = role
        ? this.roleService.updateRole(role.id, payload)
        : this.roleService.createRole(payload);

      request.subscribe({
        next: () => {
          this.snackBar.open('Rôle sauvegardé', 'Fermer', { duration: 2500 });
          this.loadRoles();
        },
        error: () => {
          this.snackBar.open('Erreur lors de la sauvegarde', 'Fermer', { duration: 4000 });
        }
      });
    });
  }

  deleteRole(role: Role): void {
    if (!confirm(`Supprimer le rôle ${role.name} ?`)) {
      return;
    }

    this.roleService.deleteRole(role.id).subscribe({
      next: () => {
        this.snackBar.open('Rôle supprimé', 'Fermer', { duration: 2500 });
        this.loadRoles();
      },
      error: () => {
        this.snackBar.open('Erreur lors de la suppression', 'Fermer', { duration: 4000 });
      }
    });
  }
}
