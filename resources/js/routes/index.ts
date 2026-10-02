import { defineStaticRoute } from '@/lib/static-route';

export const home = defineStaticRoute('/');
export const dashboard = defineStaticRoute('/dashboard');
export const login = defineStaticRoute('/login');
export const register = defineStaticRoute('/register');
export const logout = defineStaticRoute('/logout', 'post');
