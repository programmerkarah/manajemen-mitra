import { defineStaticRoute } from '@/lib/static-route';

export const send = defineStaticRoute(
    '/email/verification-notification',
    'post',
);
