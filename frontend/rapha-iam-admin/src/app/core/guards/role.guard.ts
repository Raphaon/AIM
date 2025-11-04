import { Injectable, inject } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router } from '@angular/router';
import { map, take } from 'rxjs';
import { AuthService } from '../services/auth.service';

@Injectable({ providedIn: 'root' })
export class RoleGuard implements CanActivate {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  canActivate(route: ActivatedRouteSnapshot) {
    const roles: string[] = route.data['roles'] ?? [];

    return this.authService.isAuthenticated$.pipe(
      take(1),
      map(isAuthenticated => {
        if (!isAuthenticated) {
          return this.router.createUrlTree(['/login']);
        }

        const user = this.authService.currentUser();
        if (!roles.length || !user?.roles?.length) {
          return roles.length ? this.router.createUrlTree(['/dashboard']) : true;
        }

        const hasRole = user.roles.some(role => roles.includes(role.name));
        return hasRole ? true : this.router.createUrlTree(['/dashboard']);
      })
    );
  }
}
