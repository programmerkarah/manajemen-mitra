import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { logout } from '@/routes';
import { type User } from '@/types';
import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const handleLogout = () => {
        router.post(logout().url, {}, {
            onBefore: () => router.flushAll(),
        });
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                onClick={handleLogout}
                data-test="logout-button"
                className="cursor-pointer"
            >
                <LogOut className="mr-2" />
                Log out
            </DropdownMenuItem>
        </>
    );
}
