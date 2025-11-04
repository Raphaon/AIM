import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, PaginatedCollection, Role, RolePayload } from '../models/iam.models';

@Injectable({ providedIn: 'root' })
export class RoleService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiBaseUrl}/iam/roles`;

  listRoles(): Observable<PaginatedCollection<Role>> {
    return this.http
      .get<ApiResponse<PaginatedCollection<Role>>>(this.baseUrl)
      .pipe(map(response => response.data));
  }

  getRole(id: number): Observable<Role> {
    return this.http
      .get<ApiResponse<Role>>(`${this.baseUrl}/${id}`)
      .pipe(map(response => response.data));
  }

  createRole(payload: RolePayload): Observable<Role> {
    return this.http
      .post<ApiResponse<Role>>(this.baseUrl, payload)
      .pipe(map(response => response.data));
  }

  updateRole(id: number, payload: RolePayload): Observable<Role> {
    return this.http
      .put<ApiResponse<Role>>(`${this.baseUrl}/${id}`, payload)
      .pipe(map(response => response.data));
  }

  deleteRole(id: number): Observable<void> {
    return this.http
      .delete<ApiResponse<unknown>>(`${this.baseUrl}/${id}`)
      .pipe(map(() => void 0));
  }
}
