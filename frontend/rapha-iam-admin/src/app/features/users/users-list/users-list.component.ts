import { ChangeDetectionStrategy, Component, OnInit, AfterViewInit, ViewChild, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatSort, MatSortModule } from '@angular/material/sort';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatChipsModule } from '@angular/material/chips';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { debounceTime } from 'rxjs/operators';
import { merge } from 'rxjs';
import { UserService } from '../../../core/services/user.service';
import { PaginatedCollection, User, UserPayload } from '../../../core/models/iam.models';
import { UserFormComponent, UserFormData } from '../user-form/user-form.component';

@Component({
  selector: 'app-users-list',
  standalone: true,
  imports: [
    CommonModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatDialogModule,
    MatProgressSpinnerModule,
    MatTooltipModule,
    MatChipsModule,
    MatFormFieldModule,
    MatInputModule,
    ReactiveFormsModule
  ],
  template: `
    <div class="page-container">
      <div class="header">
        <h2>Utilisateurs</h2>
        <button mat-flat-button color="primary" (click)="openForm()">
          <mat-icon>add</mat-icon>
          Nouvel utilisateur
        </button>
      </div>

      <div class="filters">
        <mat-form-field appearance="outline">
          <mat-label>Recherche</mat-label>
          <input matInput [formControl]="search" placeholder="Nom ou email" />
        </mat-form-field>
      </div>

      <div class="table-wrapper" *ngIf="dataSource; else loading">
        <table mat-table [dataSource]="dataSource" matSort>
          <ng-container matColumnDef="name">
            <th mat-header-cell *matHeaderCellDef mat-sort-header>Nom</th>
            <td mat-cell *matCellDef="let user">{{ user.name }}</td>
          </ng-container>

          <ng-container matColumnDef="email">
            <th mat-header-cell *matHeaderCellDef mat-sort-header>Email</th>
            <td mat-cell *matCellDef="let user">{{ user.email }}</td>
          </ng-container>

          <ng-container matColumnDef="roles">
            <th mat-header-cell *matHeaderCellDef>Rôles</th>
            <td mat-cell *matCellDef="let user">
              <mat-chip-set>
                <mat-chip *ngFor="let role of user.roles ?? []">{{ role.name }}</mat-chip>
              </mat-chip-set>
            </td>
          </ng-container>

          <ng-container matColumnDef="status">
            <th mat-header-cell *matHeaderCellDef mat-sort-header>Statut</th>
            <td mat-cell *matCellDef="let user">
              <span [class.active]="user.status === 'active'" [class.suspended]="user.status === 'suspended'">
                {{ user.status === 'active' ? 'Actif' : 'Suspendu' }}
              </span>
            </td>
          </ng-container>

          <ng-container matColumnDef="actions">
            <th mat-header-cell *matHeaderCellDef>Actions</th>
            <td mat-cell *matCellDef="let user">
              <button mat-icon-button color="primary" (click)="openForm(user)">
                <mat-icon>edit</mat-icon>
              </button>
              <button mat-icon-button color="warn" (click)="deleteUser(user)">
                <mat-icon>delete</mat-icon>
              </button>
            </td>
          </ng-container>

          <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
          <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
        </table>
        <mat-paginator [length]="total" [pageSize]="pageSize" [pageSizeOptions]="[10, 25, 50]"></mat-paginator>
      </div>
    </div>

    <ng-template #loading>
      <div class="loading">
        <mat-progress-spinner mode="indeterminate"></mat-progress-spinner>
      </div>
    </ng-template>
  `,
  styles: [
    `
      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
      }

      .filters {
        margin-bottom: 16px;
        max-width: 260px;
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

      td span.active {
        color: #2e7d32;
        font-weight: 600;
      }

      td span.suspended {
        color: #c62828;
        font-weight: 600;
      }

      .loading {
        display: flex;
        justify-content: center;
        padding: 40px;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class UsersListComponent implements OnInit, AfterViewInit {
  private readonly userService = inject(UserService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  displayedColumns = ['name', 'email', 'roles', 'status', 'actions'];
  dataSource = new MatTableDataSource<User>([]);
  total = 0;
  pageSize = 10;
  search = new FormControl('');

  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  ngOnInit(): void {
    this.search.valueChanges?.pipe(debounceTime(300)).subscribe(() => {
      if (this.paginator) {
      this.paginator.firstPage();
    }
      this.loadUsers();
    });
  }

  ngAfterViewInit(): void {
    merge(this.paginator.page).subscribe(() => this.loadUsers());
    this.loadUsers();
  }

  private handleResponse(response: PaginatedCollection<User>): void {
    this.total = response.total;
    this.pageSize = response.per_page;
    this.dataSource.data = response.data;
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  loadUsers(): void {
    const params = {
      search: this.search.value ?? undefined,
      page: this.paginator?.pageIndex ? this.paginator.pageIndex + 1 : 1,
      per_page: this.paginator?.pageSize ?? this.pageSize
    } as Record<string, string | number | undefined>;

    this.userService.listUsers(params).subscribe({
      next: response => this.handleResponse(response),
      error: () => {
        this.snackBar.open('Impossible de charger les utilisateurs', 'Fermer', { duration: 4000 });
      }
    });
  }

  openForm(user?: User): void {
    const dialogRef = this.dialog.open<UserFormComponent, UserFormData, UserPayload>(UserFormComponent, {
      width: '600px',
      data: { user }
    });

    dialogRef.afterClosed().subscribe(payload => {
      if (!payload) {
        return;
      }

      const request = user
        ? this.userService.updateUser(user.id, payload)
        : this.userService.createUser(payload);

      request.subscribe({
        next: () => {
          this.snackBar.open('Utilisateur sauvegardé', 'Fermer', { duration: 2500 });
          this.loadUsers();
        },
        error: () => {
          this.snackBar.open('Erreur lors de la sauvegarde', 'Fermer', { duration: 4000 });
        }
      });
    });
  }

  deleteUser(user: User): void {
    if (!confirm(`Supprimer ${user.name} ?`)) {
      return;
    }

    this.userService.deleteUser(user.id).subscribe({
      next: () => {
        this.snackBar.open('Utilisateur supprimé', 'Fermer', { duration: 2500 });
        this.loadUsers();
      },
      error: () => {
        this.snackBar.open('Erreur lors de la suppression', 'Fermer', { duration: 4000 });
      }
    });
  }
}
