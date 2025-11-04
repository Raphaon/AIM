import { Injectable, inject } from '@angular/core';
import { CanActivate, CanActivateChild, Router } from '@angular/router';
import { map, take } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate, CanActivateChild {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  canActivate(): ReturnType<CanActivate['canActivate']> {
    return this.authService.isAuthenticated$.pipe(
      take(1),
      map(isAuthenticated => (isAuthenticated ? true : this.router.createUrlTree(['/login'])))
    );
  }

  canActivateChild(): ReturnType<CanActivateChild['canActivateChild']> {
    return this.canActivate();
  }
}
