import {
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { type User } from '@/types';
import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const handleLogout = async () => {
        try {
            const response = await fetch('/csrf-token', {
                credentials: 'same-origin',
            });
            const data = (await response.json()) as { token?: string };
            const csrfToken =
                data.token ??
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ??
                '';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            document.body.appendChild(form);
            form.submit();
        } catch {
            router.post(
                '/logout',
                {},
                {
                    onBefore: () => router.flushAll(),
                },
            );
        }
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
