import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { UserDto } from '../../core/models/auth.models';
import { AuthService } from '../../core/services/auth.service';

export const profileResolver: ResolveFn<UserDto> = () => inject(AuthService).loadCurrentUser();
