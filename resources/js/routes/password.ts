import { defineStaticRoute } from '@/lib/static-route';

export const request = defineStaticRoute('/forgot-password');
export const update = defineStaticRoute('/reset-password', 'post');
