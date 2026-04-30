import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, Eye, EyeOff, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface UserOption {
    id: number;
    name: string;
    username: string;
    email: string;
}

export default function ViewAsUserSwitcher() {
    const { auth } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const [users, setUsers] = useState<UserOption[]>([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (open && search.length > 0) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Loading state for async data fetch
            setLoading(true);
            const abortController = new AbortController();

            fetch(`/view-as-user/search?search=${encodeURIComponent(search)}`, {
                signal: abortController.signal,
            })
                .then((res) => res.json())
                .then((data) => {
                    setUsers(data);

                    setLoading(false);
                })
                .catch((error) => {
                    // Ignore abort errors
                    if (error.name !== 'AbortError') {
                        setLoading(false);
                    }
                });

            return () => abortController.abort();
        }
    }, [search, open]);

    // Only show for authorized users
    if (!auth.canViewAsUser) {
        return null;
    }

    const handleSelectUser = (userId: number) => {
        router.post(
            '/view-as-user/set',
            { user_id: userId },
            {
                onSuccess: () => {
                    setOpen(false);
                    setSearch('');
                },
            },
        );
    };

    const handleClearViewAs = () => {
        router.post('/view-as-user/clear');
    };

    return (
        <div className="flex items-center gap-2">
            {auth.isViewingAsUser && (
                <div className="flex items-center gap-2 rounded-md bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-900 dark:bg-amber-900/30 dark:text-amber-400">
                    <Eye className="h-3.5 w-3.5" />
                    <span>Viewing as: {auth.user?.name}</span>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleClearViewAs}
                        className="h-5 w-5 p-0 hover:bg-amber-200 dark:hover:bg-amber-800"
                    >
                        <EyeOff className="h-3 w-3" />
                    </Button>
                </div>
            )}

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        size="sm"
                        className="gap-2 text-xs"
                        title="View as another user (rhmtzikri only)"
                    >
                        <User className="h-3.5 w-3.5" />
                        View As
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[300px] p-0" align="end">
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder="Cari user..."
                            value={search}
                            onValueChange={setSearch}
                        />
                        <CommandList>
                            {loading ? (
                                <CommandEmpty>Loading...</CommandEmpty>
                            ) : users.length === 0 ? (
                                <CommandEmpty>
                                    {search.length > 0
                                        ? 'User tidak ditemukan'
                                        : 'Ketik untuk mencari user'}
                                </CommandEmpty>
                            ) : (
                                <CommandGroup>
                                    {users.map((user) => (
                                        <CommandItem
                                            key={user.id}
                                            value={user.id.toString()}
                                            onSelect={() =>
                                                handleSelectUser(user.id)
                                            }
                                            className="cursor-pointer"
                                        >
                                            <div className="flex flex-1 flex-col">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">
                                                        {user.name}
                                                    </span>
                                                    {auth.user?.id ===
                                                        user.id && (
                                                        <Check className="h-4 w-4 text-green-600" />
                                                    )}
                                                </div>
                                                <span className="text-xs text-neutral-500">
                                                    @{user.username}
                                                </span>
                                                <span className="text-xs text-neutral-400">
                                                    {user.email}
                                                </span>
                                            </div>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            )}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
}
