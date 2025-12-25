import * as React from 'react'
import { Search, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Input } from '@/components/ui/input'

interface Option {
    value: string
    label: string
    displayLabel?: string
    disabled?: boolean
    searchKeywords?: string
}

interface SearchableSelectProps {
    options: Option[]
    value: string
    onValueChange: (value: string) => void
    placeholder?: string
    searchPlaceholder?: string
    className?: string
    disabled?: boolean
}

export function SearchableSelect({
    options,
    value,
    onValueChange,
    placeholder = 'Pilih...',
    searchPlaceholder = 'Cari...',
    className,
    disabled = false,
}: SearchableSelectProps) {
    const [open, setOpen] = React.useState(false)
    const [search, setSearch] = React.useState('')
    const containerRef = React.useRef<HTMLDivElement>(null)

    const selectedOption = options.find((option) => option.value === value)

    const filteredOptions = React.useMemo(() => {
        if (!search) return options
        const searchLower = search.toLowerCase()
        return options.filter((option) =>
            option.label.toLowerCase().includes(searchLower) ||
            (option.searchKeywords && option.searchKeywords.toLowerCase().includes(searchLower))
        )
    }, [options, search])

    // Close dropdown when clicking outside
    React.useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false)
                setSearch('')
            }
        }

        if (open) {
            document.addEventListener('mousedown', handleClickOutside)
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside)
        }
    }, [open])

    const handleSelect = (optionValue: string) => {
        onValueChange(optionValue)
        setOpen(false)
        setSearch('')
    }

    return (
        <div ref={containerRef} className="relative">
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => !disabled && setOpen(!open)}
                disabled={disabled}
                className={cn(
                    'flex h-10 w-full items-center justify-between rounded-lg border border-neutral-200/70 bg-white/50 dark:bg-neutral-800/60 backdrop-blur-md px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:hover:border-neutral-700',
                    className
                )}
            >
                <span className={cn('truncate text-left', !value && 'text-neutral-500 dark:text-neutral-400')}>
                    {selectedOption?.displayLabel || selectedOption?.label || placeholder}
                </span>
                <Search className="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </button>

            {/* Dropdown */}
            {open && (
                <div className="absolute z-50 mt-1 w-full rounded-xl border border-white/20 dark:border-neutral-700/30 bg-white/95 dark:bg-neutral-800/95 backdrop-blur-xl shadow-2xl">
                    {/* Search Input */}
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

                    {/* Options List */}
                    <div className="max-h-60 overflow-y-auto p-1">
                        {filteredOptions.length === 0 ? (
                            <div className="py-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                Tidak ada hasil
                            </div>
                        ) : (
                            filteredOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => !option.disabled && handleSelect(option.value)}
                                    disabled={option.disabled}
                                    className={cn(
                                        'flex w-full items-center rounded-md px-2 py-2 text-left text-sm transition-colors',
                                        value === option.value
                                            ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                            : 'hover:bg-neutral-100 dark:hover:bg-neutral-900',
                                        option.disabled && 'cursor-not-allowed opacity-50'
                                    )}
                                >
                                    <span className="flex-1 truncate">{option.label}</span>
                                    {option.disabled && (
                                        <span className="ml-2 text-xs text-neutral-500 dark:text-neutral-400">
                                            (sudah dipilih)
                                        </span>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    )
}
