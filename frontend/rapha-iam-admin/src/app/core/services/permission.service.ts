import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, PaginatedCollection, Permission, PermissionPayload } from '../models/iam.models';

@Injectable({ providedIn: 'root' })
export class PermissionService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiBaseUrl}/iam/permissions`;

  listPermissions(): Observable<PaginatedCollection<Permission>> {
    return this.http
      .get<ApiResponse<PaginatedCollection<Permission>>>(this.baseUrl)
      .pipe(map(response => response.data));
  }

  createPermission(payload: PermissionPayload): Observable<Permission> {
    return this.http
      .post<ApiResponse<Permission>>(this.baseUrl, payload)
      .pipe(map(response => response.data));
  }

  updatePermission(id: number, payload: PermissionPayload): Observable<Permission> {
    return this.http
      .put<ApiResponse<Permission>>(`${this.baseUrl}/${id}`, payload)
      .pipe(map(response => response.data));
  }

  deletePermission(id: number): Observable<void> {
    return this.http
      .delete<ApiResponse<unknown>>(`${this.baseUrl}/${id}`)
      .pipe(map(() => void 0));
  }
}
