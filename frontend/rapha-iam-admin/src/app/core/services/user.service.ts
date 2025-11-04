import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, PaginatedCollection, User, UserPayload } from '../models/iam.models';

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiBaseUrl}/iam/users`;

  listUsers(filters: Record<string, string | number | undefined> = {}): Observable<PaginatedCollection<User>> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, String(value));
      }
    });

    return this.http
      .get<ApiResponse<PaginatedCollection<User>>>(this.baseUrl, { params })
      .pipe(map(response => response.data));
  }

  getUser(id: number): Observable<User> {
    return this.http
      .get<ApiResponse<User>>(`${this.baseUrl}/${id}`)
      .pipe(map(response => response.data));
  }

  createUser(payload: UserPayload): Observable<User> {
    return this.http
      .post<ApiResponse<User>>(this.baseUrl, payload)
      .pipe(map(response => response.data));
  }

  updateUser(id: number, payload: UserPayload): Observable<User> {
    return this.http
      .put<ApiResponse<User>>(`${this.baseUrl}/${id}`, payload)
      .pipe(map(response => response.data));
  }

  deleteUser(id: number): Observable<void> {
    return this.http
      .delete<ApiResponse<unknown>>(`${this.baseUrl}/${id}`)
      .pipe(map(() => void 0));
  }
}
