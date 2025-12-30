import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import {
    Award,
    Briefcase,
    Check,
    ChevronDown,
    Crown,
    KeyRound,
    Shield,
    User,
    Users,
} from 'lucide-react';
import { useState } from 'react';

export default function RoleSwitcher() {
    const { auth } = usePage<SharedData>().props;

    const [switching, setSwitching] = useState(false);

    const handleRoleSwitch = (roleId: number) => {
        if (switching) return;

        setSwitching(true);
        router.post(
            '/switch-role',
            { role_id: roleId },
            {
                preserveScroll: false,
                preserveState: false,
                onFinish: () => setSwitching(false),
            },
        );
    };

    if (!auth.userRoles || auth.userRoles.length <= 1) {
        // User only has one role, no need to show switcher
        return null;
    }

    // Map role name to unique icon
    const getRoleIcon = (roleName?: string) => {
        switch (roleName) {
            case 'admin':
                return <Crown className="size-4 text-yellow-500" />;
            case 'operator':
                return <Briefcase className="size-4 text-blue-500" />;
            case 'petugas':
                return <User className="size-4 text-green-500" />;
            case 'approver':
                return <KeyRound className="size-4 text-purple-500" />;
            case 'pj':
                return <Award className="size-4 text-pink-500" />;
            case 'ketua_tim':
                return <Users className="size-4 text-cyan-500" />;
            default:
                return <Shield className="size-4 text-gray-400" />;
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-2"
                    disabled={switching}
                >
                    {getRoleIcon(auth.activeRole?.name)}
                    <span className="hidden sm:inline">
                        {auth.activeRole?.display_name || 'Pilih Role'}
                    </span>
                    <ChevronDown className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel>Ganti Role Aktif</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {auth.userRoles.map((role) => (
                    <DropdownMenuItem
                        key={role.id}
                        onClick={() => handleRoleSwitch(role.id)}
                        disabled={switching}
                        className="flex items-center justify-between"
                    >
                        <span>{role.display_name}</span>
                        {auth.activeRole?.id === role.id && (
                            <Check className="size-4 text-green-600" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
