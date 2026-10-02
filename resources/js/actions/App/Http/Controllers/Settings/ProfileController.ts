import { defineStaticRoute } from '@/lib/static-route';

const ProfileController = {
    destroy: defineStaticRoute('/settings/profile', 'delete'),
};

export default ProfileController;
