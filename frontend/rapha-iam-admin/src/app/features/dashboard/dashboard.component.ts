import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { catchError, combineLatest, map, of, startWith } from 'rxjs';
import { UserService } from '../../core/services/user.service';
import { RoleService } from '../../core/services/role.service';
import { PermissionService } from '../../core/services/permission.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, MatCardModule, MatProgressSpinnerModule],
  template: `
    <div class="page-container" *ngIf="stats$ | async as stats; else loading">
      <div class="grid">
        <mat-card class="stat-card">
          <h3>Utilisateurs</h3>
          <p>{{ stats.users }}</p>
        </mat-card>
        <mat-card class="stat-card">
          <h3>Rôles</h3>
          <p>{{ stats.roles }}</p>
        </mat-card>
        <mat-card class="stat-card">
          <h3>Permissions</h3>
          <p>{{ stats.permissions }}</p>
        </mat-card>
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
      .grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      }

      .stat-card {
        text-align: center;
      }

      .stat-card h3 {
        margin-bottom: 8px;
        font-weight: 500;
      }

      .stat-card p {
        font-size: 2.4rem;
        margin: 0;
        color: #3f51b5;
      }

      .loading {
        display: flex;
        justify-content: center;
        padding: 48px 0;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class DashboardComponent {
  private readonly userService = inject(UserService);
  private readonly roleService = inject(RoleService);
  private readonly permissionService = inject(PermissionService);

  stats$ = combineLatest([
    this.userService.listUsers({ per_page: 1 }).pipe(
      map(response => response.total ?? response.data.length),
      startWith(0),
      catchError(() => of(0))
    ),
    this.roleService.listRoles().pipe(
      map(response => response.total ?? response.data.length),
      startWith(0),
      catchError(() => of(0))
    ),
    this.permissionService.listPermissions().pipe(
      map(response => response.total ?? response.data.length),
      startWith(0),
      catchError(() => of(0))
    )
  ]).pipe(map(([users, roles, permissions]) => ({ users, roles, permissions })));
}
