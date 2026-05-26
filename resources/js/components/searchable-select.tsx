import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Search, X } from 'lucide-react';
import * as React from 'react';
import { createPortal } from 'react-dom';

interface Option {
    value: string;
    label: string;
    displayLabel?: string;
    disabled?: boolean;
    searchKeywords?: string;
}

interface SearchableSelectProps {
    options: Option[];
    value: string;
    onValueChange: (value: string) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    className?: string;
    disabled?: boolean;
    defaultVisibleCount?: number;
}

export function SearchableSelect({
    options,
    value,
    onValueChange,
    placeholder = 'Pilih...',
    searchPlaceholder = 'Cari...',
    className,
    disabled = false,
    defaultVisibleCount,
}: SearchableSelectProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');
    const containerRef = React.useRef<HTMLDivElement>(null);
    const dropdownRef = React.useRef<HTMLDivElement>(null);
    const [dropdownStyle, setDropdownStyle] = React.useState<{
        top: number;
        left: number;
        width: number;
        maxHeight: number;
    }>({ top: 0, left: 0, width: 0, maxHeight: 240 });

    const selectedOption = options.find((option) => option.value === value);

    const filteredOptions = React.useMemo(() => {
        if (!search) {
            if (!defaultVisibleCount || defaultVisibleCount <= 0) {
                return options;
            }

            return options.slice(0, defaultVisibleCount);
        }

        const searchLower = search.toLowerCase();
        return options.filter(
            (option) =>
                option.label.toLowerCase().includes(searchLower) ||
                (option.searchKeywords &&
                    option.searchKeywords.toLowerCase().includes(searchLower)),
        );
    }, [options, search, defaultVisibleCount]);

    // Close dropdown when clicking outside
    React.useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            const targetNode = event.target as Node;
            const clickedInsideContainer =
                containerRef.current?.contains(targetNode) ?? false;
            const clickedInsideDropdown =
                dropdownRef.current?.contains(targetNode) ?? false;

            if (clickedInsideContainer || clickedInsideDropdown) {
                return;
            }

            setOpen(false);
            setSearch('');
        };

        if (open) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [open]);

    React.useEffect(() => {
        if (!open) {
            return;
        }

        const updateDropdownPosition = () => {
            if (!containerRef.current) {
                return;
            }

            const rect = containerRef.current.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const edgePadding = 8;

            const computedWidth = Math.min(
                rect.width,
                viewportWidth - edgePadding * 2,
            );
            const computedLeft = Math.min(
                Math.max(rect.left, edgePadding),
                viewportWidth - computedWidth - edgePadding,
            );

            const spaceBelow = viewportHeight - rect.bottom - edgePadding;
            const spaceAbove = rect.top - edgePadding;
            const shouldOpenUpwards =
                spaceBelow < 220 && spaceAbove > spaceBelow;
            const maxHeight = Math.max(
                160,
                Math.min(
                    320,
                    shouldOpenUpwards ? spaceAbove - 8 : spaceBelow - 8,
                ),
            );

            setDropdownStyle({
                top: shouldOpenUpwards
                    ? Math.max(edgePadding, rect.top - maxHeight - 4)
                    : rect.bottom + 4,
                left: computedLeft,
                width: computedWidth,
                maxHeight,
            });
        };

        updateDropdownPosition();

        window.addEventListener('resize', updateDropdownPosition);
        window.addEventListener('scroll', updateDropdownPosition, true);

        return () => {
            window.removeEventListener('resize', updateDropdownPosition);
            window.removeEventListener('scroll', updateDropdownPosition, true);
        };
    }, [open]);

    const handleSelect = (optionValue: string) => {
        onValueChange(optionValue);
        setOpen(false);
        setSearch('');
    };

    return (
        <div ref={containerRef} className={cn('relative', open && 'z-[70]')}>
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => !disabled && setOpen(!open)}
                disabled={disabled}
                className={cn(
                    'flex h-10 w-full items-center justify-between rounded-lg border border-neutral-200/70 bg-white/50 px-3 py-2 text-sm shadow-sm backdrop-blur-md transition-colors hover:border-neutral-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-800/60 dark:hover:border-neutral-700',
                    className,
                )}
            >
                <span
                    className={cn(
                        'truncate text-left',
                        !value && 'text-neutral-500 dark:text-neutral-400',
                    )}
                >
                    {selectedOption?.displayLabel ||
                        selectedOption?.label ||
                        placeholder}
                </span>
                <Search className="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </button>

            {/* Dropdown */}
            {open &&
                typeof document !== 'undefined' &&
                createPortal(
                    <div
                        ref={dropdownRef}
                        className="fixed z-[9999] rounded-xl border border-white/20 bg-white/95 shadow-2xl backdrop-blur-xl dark:border-neutral-700/30 dark:bg-neutral-800/95"
                        style={{
                            top: dropdownStyle.top,
                            left: dropdownStyle.left,
                            width: dropdownStyle.width,
                            maxHeight: dropdownStyle.maxHeight,
                        }}
                    >
                        <div className="flex items-center border-b border-neutral-200 px-3 dark:border-neutral-800">
                            <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                            <Input
                                type="text"
                                placeholder={searchPlaceholder}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="h-10 border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                                autoFocus
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
                                    className="ml-2 hover:opacity-70"
                                >
                                    <X className="h-4 w-4 opacity-50" />
                                </button>
                            )}
                        </div>

                        <div
                            className="overflow-y-auto p-1"
                            style={{ maxHeight: dropdownStyle.maxHeight - 48 }}
                        >
                            {filteredOptions.length === 0 ? (
                                <div className="py-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                    Tidak ada hasil
                                </div>
                            ) : (
                                filteredOptions.map((option) => (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() =>
                                            !option.disabled &&
                                            handleSelect(option.value)
                                        }
                                        disabled={option.disabled}
                                        className={cn(
                                            'flex w-full items-center rounded-md px-2 py-2 text-left text-sm transition-colors',
                                            value === option.value
                                                ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                                : 'hover:bg-neutral-100 dark:hover:bg-neutral-900',
                                            option.disabled &&
                                                'cursor-not-allowed opacity-50',
                                        )}
                                    >
                                        <span className="flex-1 truncate">
                                            {option.label}
                                        </span>
                                        {option.disabled && (
                                            <span className="ml-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                (sudah dipilih)
                                            </span>
                                        )}
                                    </button>
                                ))
                            )}
                        </div>
                    </div>,
                    document.body,
                )}
        </div>
    );
}
