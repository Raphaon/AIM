import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable, map, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, AuthResponse, LoginCredentials, User } from '../models/iam.models';

const TOKEN_KEY = 'rapha_iam_token';
const SESSION_TOKEN = 'session';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  private readonly token$ = new BehaviorSubject<string | null>(this.getStoredToken());
  private readonly userSignal = signal<User | null>(null);

  readonly isAuthenticated$ = this.token$.pipe(map(token => token !== null));
  readonly currentUser = computed(() => this.userSignal());

  constructor() {
    const token = this.getStoredToken();
    if (token) {
      this.getProfile().subscribe();
    }
  }

  login(credentials: LoginCredentials): Observable<AuthResponse> {
    return this.http
      .post<ApiResponse<AuthResponse>>(`${environment.apiBaseUrl}/iam/auth/login`, credentials)
      .pipe(
        map(response => response.data),
        tap(response => {
          const sessionToken = response.token ?? SESSION_TOKEN;
          this.storeToken(sessionToken);
          this.userSignal.set(response.user);
          this.token$.next(sessionToken);
          void this.router.navigate(['/dashboard']);
        })
      );
  }

  logout(): void {
    const complete = () => this.resetState();

    this.http.post<ApiResponse<unknown>>(`${environment.apiBaseUrl}/iam/auth/logout`, {}).subscribe({
      next: complete,
      error: complete
    });
  }

  getProfile(): Observable<User> {
    return this.http
      .get<ApiResponse<User>>(`${environment.apiBaseUrl}/iam/auth/profile`)
      .pipe(
        map(response => response.data),
        tap({
          next: user => {
            this.userSignal.set(user);
            if (!this.token$.value) {
              this.token$.next(SESSION_TOKEN);
              this.storeToken(SESSION_TOKEN);
            }
          },
          error: () => this.resetState()
        })
      );
  }

  getToken(): string | null {
    return this.token$.value;
  }

  private getStoredToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  private storeToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
  }

  private resetState(): void {
    localStorage.removeItem(TOKEN_KEY);
    this.token$.next(null);
    this.userSignal.set(null);
    void this.router.navigate(['/login']);
  }
}
