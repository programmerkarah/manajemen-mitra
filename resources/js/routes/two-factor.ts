import { defineStaticRoute } from '@/lib/static-route';

export const show = defineStaticRoute('/settings/two-factor');
export const qrCode = defineStaticRoute('/user/two-factor-qr-code');
export const recoveryCodes = defineStaticRoute(
    '/user/two-factor-recovery-codes',
);
export const secretKey = defineStaticRoute('/user/two-factor-secret-key');
export const confirm = defineStaticRoute(
    '/user/confirmed-two-factor-authentication',
    'post',
);
export const regenerateRecoveryCodes = defineStaticRoute(
    '/user/two-factor-recovery-codes',
    'post',
);
