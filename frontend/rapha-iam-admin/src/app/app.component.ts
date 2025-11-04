import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatListModule } from '@angular/material/list';
import { RouterModule } from '@angular/router';
import { AsyncPipe, NgIf } from '@angular/common';
import { AuthService } from './core/services/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    RouterModule,
    MatToolbarModule,
    MatButtonModule,
    MatIconModule,
    MatSidenavModule,
    MatListModule,
    NgIf,
    AsyncPipe
  ],
  template: `
    <mat-toolbar color="primary">
      <span>Rapha IAM Admin</span>
      <span class="spacer"></span>
      <button
        mat-button
        *ngIf="authService.isAuthenticated$ | async"
        (click)="authService.logout()"
        data-testid="logout-button"
      >
        Déconnexion
      </button>
    </mat-toolbar>

    <div class="layout">
      <mat-sidenav-container>
        <mat-sidenav
          mode="side"
          opened
          class="sidenav"
          *ngIf="authService.isAuthenticated$ | async"
        >
          <mat-nav-list>
            <a mat-list-item routerLink="/dashboard" routerLinkActive="active">Dashboard</a>
            <a mat-list-item routerLink="/users" routerLinkActive="active">Utilisateurs</a>
            <a mat-list-item routerLink="/roles" routerLinkActive="active">Rôles</a>
            <a mat-list-item routerLink="/permissions" routerLinkActive="active">Permissions</a>
          </mat-nav-list>
        </mat-sidenav>
        <mat-sidenav-content>
          <main class="content">
            <router-outlet />
          </main>
        </mat-sidenav-content>
      </mat-sidenav-container>
    </div>
  `,
  styles: [
    `
      .layout {
        height: calc(100vh - 64px);
      }

      .content {
        padding: 24px;
      }

      .sidenav {
        width: 220px;
      }

      .spacer {
        flex: 1 1 auto;
      }
    `
  ],
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class AppComponent {
  protected readonly authService = inject(AuthService);
}
