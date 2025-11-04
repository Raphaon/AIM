import { ChangeDetectionStrategy, Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Permission, PermissionPayload } from '../../../core/models/iam.models';
import { PermissionService } from '../../../core/services/permission.service';
import { PermissionFormComponent, PermissionFormData } from '../permission-form/permission-form.component';

@Component({
  selector: 'app-permissions-list',
  standalone: true,
  imports: [CommonModule, MatTableModule, MatButtonModule, MatIconModule, MatDialogModule],
  template: `
    <div class="page-container">
      <div class="header">
        <h2>Permissions</h2>
        <button mat-flat-button color="primary" (click)="openForm()">
          <mat-icon>add</mat-icon>
          Nouvelle permission
        </button>
      </div>

      <div class="table-wrapper">
        <table mat-table [dataSource]="dataSource">
          <ng-container matColumnDef="name">
            <th mat-header-cell *matHeaderCellDef>Nom</th>
            <td mat-cell *matCellDef="let permission">{{ permission.name }}</td>
          </ng-container>

          <ng-container matColumnDef="slug">
            <th mat-header-cell *matHeaderCellDef>Slug</th>
            <td mat-cell *matCellDef="let permission">{{ permission.slug }}</td>
          </ng-container>

          <ng-container matColumnDef="description">
            <th mat-header-cell *matHeaderCellDef>Description</th>
            <td mat-cell *matCellDef="let permission">{{ permission.description || '—' }}</td>
          </ng-container>

          <ng-container matColumnDef="actions">
            <th mat-header-cell *matHeaderCellDef>Actions</th>
            <td mat-cell *matCellDef="let permission">
              <button mat-icon-button color="primary" (click)="openForm(permission)">
                <mat-icon>edit</mat-icon>
              </button>
              <button mat-icon-button color="warn" (click)="deletePermission(permission)">
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
export class PermissionsListComponent implements OnInit {
  private readonly permissionService = inject(PermissionService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  displayedColumns = ['name', 'slug', 'description', 'actions'];
  dataSource = new MatTableDataSource<Permission>([]);

  ngOnInit(): void {
    this.loadPermissions();
  }

  loadPermissions(): void {
    this.permissionService.listPermissions().subscribe({
      next: response => {
        this.dataSource.data = response.data;
      },
      error: () => {
        this.snackBar.open('Impossible de charger les permissions', 'Fermer', { duration: 4000 });
      }
    });
  }

  openForm(permission?: Permission): void {
    const dialogRef = this.dialog.open<PermissionFormComponent, PermissionFormData, PermissionPayload>(
      PermissionFormComponent,
      {
        width: '480px',
        data: { permission }
      }
    );

    dialogRef.afterClosed().subscribe(payload => {
      if (!payload) {
        return;
      }

      const request = permission
        ? this.permissionService.updatePermission(permission.id, payload)
        : this.permissionService.createPermission(payload);

      request.subscribe({
        next: () => {
          this.snackBar.open('Permission sauvegardée', 'Fermer', { duration: 2500 });
          this.loadPermissions();
        },
        error: () => {
          this.snackBar.open('Erreur lors de la sauvegarde', 'Fermer', { duration: 4000 });
        }
      });
    });
  }

  deletePermission(permission: Permission): void {
    if (!confirm(`Supprimer ${permission.name} ?`)) {
      return;
    }

    this.permissionService.deletePermission(permission.id).subscribe({
      next: () => {
        this.snackBar.open('Permission supprimée', 'Fermer', { duration: 2500 });
        this.loadPermissions();
      },
      error: () => {
        this.snackBar.open('Erreur lors de la suppression', 'Fermer', { duration: 4000 });
      }
    });
  }
}
