import { router, usePage } from '@inertiajs/react'
import { Check, ChevronDown, Shield } from 'lucide-react'
import { useState } from 'react'
import type { SharedData } from '@/types'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'

export default function RoleSwitcher() {
    const { auth } = usePage<SharedData>().props

    const [switching, setSwitching] = useState(false)

    const handleRoleSwitch = (roleId: number) => {
        if (switching) return

        setSwitching(true)
        router.post(
            '/switch-role',
            { role_id: roleId },
            {
                preserveScroll: true,
                onFinish: () => setSwitching(false),
            }
        )
    }

    if (!auth.userRoles || auth.userRoles.length <= 1) {
        // User only has one role, no need to show switcher
        return null
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-2"
                    disabled={switching}
                >
                    <Shield className="size-4" />
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
    )
}


