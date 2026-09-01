import { cn } from '@/lib/utils';
import { Check, ChevronDown, X } from 'lucide-react';
import * as React from 'react';
import { createPortal } from 'react-dom';

interface MultiSelectOption {
    value: number;
    label: string;
    subLabel?: string;
}

interface MultiSelectCheckboxProps {
    options: MultiSelectOption[];
    values: number[];
    onValuesChange: (values: number[]) => void;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
}

export function MultiSelectCheckbox({
    options,
    values,
    onValuesChange,
    placeholder = 'Pilih...',
    className,
    disabled = false,
}: MultiSelectCheckboxProps) {
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

    const selectedOptions = options.filter((opt) => values.includes(opt.value));
    const filteredOptions = React.useMemo(() => {
        if (!search.trim()) {
            return options;
        }

        const query = search.trim().toLowerCase();

        return options.filter((option) => {
            const label = option.label.toLowerCase();
            const subLabel = option.subLabel?.toLowerCase() ?? '';

            return label.includes(query) || subLabel.includes(query);
        });
    }, [options, search]);

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

    const handleToggle = (optionValue: number) => {
        if (values.includes(optionValue)) {
            onValuesChange(values.filter((v) => v !== optionValue));
        } else {
            onValuesChange([...values, optionValue]);
        }
    };

    const handleRemove = (e: React.MouseEvent, optionValue: number) => {
        e.stopPropagation();
        onValuesChange(values.filter((v) => v !== optionValue));
    };

    const triggerLabel = React.useMemo(() => {
        if (selectedOptions.length === 0) {
            return null;
        }

        if (selectedOptions.length <= 2) {
            return selectedOptions.map((opt) => opt.label).join(', ');
        }

        return `${selectedOptions.length} dipilih`;
    }, [selectedOptions]);

    return (
        <div ref={containerRef} className={cn('relative', open && 'z-[70]')}>
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => !disabled && setOpen(!open)}
                disabled={disabled}
                className={cn(
                    'flex min-h-10 w-full items-center justify-between rounded-lg border border-neutral-200/70 bg-white/50 px-3 py-2 text-sm shadow-sm backdrop-blur-md transition-colors hover:border-neutral-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-800/60 dark:hover:border-neutral-700',
                    className,
                )}
            >
                <span
                    className={cn(
                        'truncate text-left',
                        !triggerLabel &&
                            'text-neutral-500 dark:text-neutral-400',
                    )}
                >
                    {triggerLabel ?? placeholder}
                </span>
                <ChevronDown
                    className={cn(
                        'ml-2 h-4 w-4 shrink-0 opacity-50 transition-transform',
                        open && 'rotate-180',
                    )}
                />
            </button>

            {/* Selected pills (shown below trigger when > 2 selected) */}
            {selectedOptions.length > 2 && (
                <div className="mt-1.5 flex flex-wrap gap-1">
                    {selectedOptions.map((opt) => (
                        <span
                            key={opt.value}
                            className="inline-flex items-center gap-1 rounded-md bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                        >
                            {opt.label}
                            {!disabled && (
                                <button
                                    type="button"
                                    onClick={(e) => handleRemove(e, opt.value)}
                                    className="ml-0.5 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800"
                                >
                                    <X className="h-3 w-3" />
                                </button>
                            )}
                        </span>
                    ))}
                </div>
            )}

            {/* Dropdown */}
            {open &&
                typeof document !== 'undefined' &&
                createPortal(
                    <div
                        ref={dropdownRef}
                        className="fixed z-[9999] overflow-y-auto rounded-xl border border-white/20 bg-white/95 shadow-2xl backdrop-blur-xl dark:border-neutral-700/30 dark:bg-neutral-800/95"
                        style={{
                            top: dropdownStyle.top,
                            left: dropdownStyle.left,
                            width: dropdownStyle.width,
                            maxHeight: dropdownStyle.maxHeight,
                        }}
                    >
                        <div className="border-b border-neutral-200 px-3 py-2 dark:border-neutral-800">
                            <input
                                type="text"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari nama petugas..."
                                className="h-9 w-full rounded-md border border-neutral-200 bg-white px-2.5 text-sm ring-0 outline-none placeholder:text-neutral-400 focus:border-blue-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100 dark:placeholder:text-neutral-500"
                                autoFocus
                            />
                        </div>
                        {filteredOptions.length === 0 ? (
                            <div className="px-3 py-4 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                Tidak ada pilihan tersedia
                            </div>
                        ) : (
                            <ul
                                className="py-1"
                                role="listbox"
                                aria-multiselectable="true"
                            >
                                {filteredOptions.map((option) => {
                                    const isSelected = values.includes(
                                        option.value,
                                    );
                                    return (
                                        <li
                                            key={option.value}
                                            role="option"
                                            aria-selected={isSelected}
                                            onClick={() =>
                                                handleToggle(option.value)
                                            }
                                            className="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-700"
                                        >
                                            <div
                                                className={cn(
                                                    'flex h-4 w-4 shrink-0 items-center justify-center rounded border border-neutral-300 transition-colors dark:border-neutral-600',
                                                    isSelected &&
                                                        'border-blue-600 bg-blue-600 dark:border-blue-500 dark:bg-blue-500',
                                                )}
                                            >
                                                {isSelected && (
                                                    <Check className="h-3 w-3 text-white" />
                                                )}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {option.label}
                                                </div>
                                                {option.subLabel && (
                                                    <div className="truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                        {option.subLabel}
                                                    </div>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>,
                    document.body,
                )}
        </div>
    );
}
