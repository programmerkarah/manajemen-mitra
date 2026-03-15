import { useEffect, useState } from 'react';
import { CalendarIcon, ChevronLeft, ChevronRight } from 'lucide-react';

import { cn } from '@/lib/utils';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const HARI_SINGKAT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const NAMA_BULAN = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];
const NAMA_BULAN_SINGKAT = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
];
const MIN_YEAR = 1900;

interface DatePickerProps {
    value?: string;
    onChange?: (value: string) => void;
    disabled?: boolean;
    min?: string;
    max?: string;
    placeholder?: string;
    className?: string;
    id?: string;
    name?: string;
    required?: boolean;
}

const toDateStr = (year: number, month: number, day: number): string =>
    `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

const formatDisplay = (dateStr: string): string => {
    const d = new Date(dateStr + 'T00:00:00');
    return `${d.getDate()} ${NAMA_BULAN[d.getMonth()]} ${d.getFullYear()}`;
};

type CalendarView = 'days' | 'months' | 'years';

export function DatePicker({
    value,
    onChange,
    disabled = false,
    min,
    max,
    placeholder = 'Pilih tanggal...',
    className,
    id,
    name,
    required,
}: DatePickerProps) {
    const [open, setOpen] = useState(false);
    const [internalValue, setInternalValue] = useState('');
    const [calView, setCalView] = useState<CalendarView>('days');

    const isControlled = value !== undefined;
    const resolvedValue = isControlled ? value : internalValue;

    const today = new Date();

    const deriveViewDate = (dateStr: string | undefined) => {
        if (dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            return { year: d.getFullYear(), month: d.getMonth() };
        }
        return { year: today.getFullYear(), month: today.getMonth() };
    };

    const initial = deriveViewDate(resolvedValue);
    const [viewYear, setViewYear] = useState(initial.year);
    const [viewMonth, setViewMonth] = useState(initial.month);
    const [yearRangeStart, setYearRangeStart] = useState(
        Math.floor(initial.year / 12) * 12,
    );

    useEffect(() => {
        if (resolvedValue) {
            const { year, month } = deriveViewDate(resolvedValue);
            setViewYear(year);
            setViewMonth(month);
        }
    }, [resolvedValue]);

    // Reset to days view when popover closes
    useEffect(() => {
        if (!open) {
            setCalView('days');
        }
    }, [open]);

    const handleSelect = (day: number) => {
        const dateStr = toDateStr(viewYear, viewMonth + 1, day);
        if (isControlled) {
            onChange?.(dateStr);
        } else {
            setInternalValue(dateStr);
            onChange?.(dateStr);
        }
        setOpen(false);
    };

    const prevMonth = () => {
        if (viewMonth === 0) {
            setViewMonth(11);
            setViewYear((y) => y - 1);
        } else {
            setViewMonth((m) => m - 1);
        }
    };

    const nextMonth = () => {
        if (viewMonth === 11) {
            setViewMonth(0);
            setViewYear((y) => y + 1);
        } else {
            setViewMonth((m) => m + 1);
        }
    };

    const goToday = () => {
        setViewYear(today.getFullYear());
        setViewMonth(today.getMonth());
        setYearRangeStart(Math.floor(today.getFullYear() / 12) * 12);
        if (!isDayDisabled(today.getDate())) {
            handleSelect(today.getDate());
        }
    };

    const isDayDisabled = (day: number): boolean => {
        const dateStr = toDateStr(viewYear, viewMonth + 1, day);
        if (min && dateStr < min) {
            return true;
        }
        if (max && dateStr > max) {
            return true;
        }
        return false;
    };

    const isSelectedDay = (day: number): boolean => {
        if (!resolvedValue) {
            return false;
        }
        return toDateStr(viewYear, viewMonth + 1, day) === resolvedValue;
    };

    const isTodayDay = (day: number): boolean =>
        viewYear === today.getFullYear() &&
        viewMonth === today.getMonth() &&
        day === today.getDate();

    const firstDayOfMonth = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

    const calendarCells: (number | null)[] = [];
    for (let i = 0; i < firstDayOfMonth; i++) {
        calendarCells.push(null);
    }
    for (let d = 1; d <= daysInMonth; d++) {
        calendarCells.push(d);
    }
    while (calendarCells.length % 7 !== 0) {
        calendarCells.push(null);
    }

    const handleSelectMonth = (monthIdx: number) => {
        setViewMonth(monthIdx);
        setCalView('days');
    };

    const handleSelectYear = (year: number) => {
        setViewYear(year);
        setYearRangeStart(Math.floor(year / 12) * 12);
        setCalView('months');
    };

    const prevYearRange = () => {
        setYearRangeStart((s) => Math.max(MIN_YEAR, s - 12));
    };

    const nextYearRange = () => {
        setYearRangeStart((s) => s + 12);
    };

    const selectedYear = resolvedValue
        ? new Date(resolvedValue + 'T00:00:00').getFullYear()
        : null;
    const selectedMonth = resolvedValue
        ? new Date(resolvedValue + 'T00:00:00').getMonth()
        : null;

    return (
        <>
            {name !== undefined && (
                <input
                    type="hidden"
                    name={name}
                    value={resolvedValue}
                    required={required}
                />
            )}
            <Popover
                open={open}
                onOpenChange={(o) => {
                    if (!disabled) {
                        setOpen(o);
                    }
                }}
            >
                <PopoverTrigger asChild>
                    <button
                        type="button"
                        id={id}
                        disabled={disabled}
                        className={cn(
                            'flex h-9 w-full items-center gap-2 rounded-lg border bg-white/50 px-3 text-sm backdrop-blur-md',
                            'border-neutral-200 text-left dark:border-neutral-700 dark:bg-neutral-800/60',
                            'shadow-sm transition-all duration-150 hover:border-neutral-300 hover:shadow-md',
                            'focus:outline-none focus:ring-2 focus:ring-neutral-400/30 dark:hover:border-neutral-600',
                            disabled &&
                                'cursor-not-allowed bg-neutral-100 opacity-70 dark:bg-neutral-900',
                            !resolvedValue &&
                                'text-neutral-400 dark:text-neutral-500',
                            className,
                        )}
                    >
                        <CalendarIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <span className="flex-1 truncate">
                            {resolvedValue
                                ? formatDisplay(resolvedValue)
                                : placeholder}
                        </span>
                    </button>
                </PopoverTrigger>

                <PopoverContent
                    className="w-72 p-0"
                    align="start"
                    sideOffset={6}
                >
                    {/* ── DAYS VIEW ── */}
                    {calView === 'days' && (
                        <>
                            {/* Month / Year Navigation */}
                            <div className="flex items-center justify-between border-b border-neutral-200/40 px-3 py-2 dark:border-neutral-700/40">
                                <button
                                    type="button"
                                    onClick={prevMonth}
                                    className="rounded-lg p-1.5 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={() => setCalView('months')}
                                        className="rounded-md px-2 py-0.5 text-sm font-semibold transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                    >
                                        {NAMA_BULAN[viewMonth]}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setYearRangeStart(Math.floor(viewYear / 12) * 12);
                                            setCalView('years');
                                        }}
                                        className="rounded-md px-2 py-0.5 text-sm font-semibold transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                    >
                                        {viewYear}
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    onClick={nextMonth}
                                    className="rounded-lg p-1.5 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>

                            {/* Day-of-week headers */}
                            <div className="grid grid-cols-7 px-3 pt-2">
                                {HARI_SINGKAT.map((h) => (
                                    <div
                                        key={h}
                                        className="pb-1 text-center text-xs font-medium text-neutral-400 dark:text-neutral-500"
                                    >
                                        {h}
                                    </div>
                                ))}
                            </div>

                            {/* Day grid */}
                            <div className="grid grid-cols-7 gap-0.5 px-3 pb-2">
                                {calendarCells.map((day, idx) =>
                                    day === null ? (
                                        <div key={`empty-${idx}`} />
                                    ) : (
                                        <button
                                            key={day}
                                            type="button"
                                            disabled={isDayDisabled(day)}
                                            onClick={() => handleSelect(day)}
                                            className={cn(
                                                'mx-auto flex h-8 w-8 items-center justify-center rounded-lg text-sm transition-colors',
                                                isSelectedDay(day)
                                                    ? 'bg-neutral-900 font-semibold text-white dark:bg-neutral-100 dark:text-neutral-900'
                                                    : isTodayDay(day)
                                                      ? 'border border-neutral-400/40 font-semibold text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800'
                                                      : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800',
                                                isDayDisabled(day) &&
                                                    'cursor-not-allowed opacity-30',
                                            )}
                                        >
                                            {day}
                                        </button>
                                    ),
                                )}
                            </div>

                            {/* Footer: Go to today */}
                            <div className="border-t border-neutral-200/40 px-3 py-2 dark:border-neutral-700/40">
                                <button
                                    type="button"
                                    onClick={goToday}
                                    className="w-full rounded-lg py-1.5 text-xs font-medium text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                                >
                                    Hari Ini
                                </button>
                            </div>
                        </>
                    )}

                    {/* ── MONTHS VIEW ── */}
                    {calView === 'months' && (
                        <>
                            <div className="flex items-center justify-between border-b border-neutral-200/40 px-3 py-2 dark:border-neutral-700/40">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setYearRangeStart(Math.floor(viewYear / 12) * 12);
                                        setCalView('years');
                                    }}
                                    className="rounded-md px-2 py-0.5 text-sm font-semibold transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    {viewYear}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setCalView('days')}
                                    className="rounded-md px-2 py-0.5 text-xs text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    ✕
                                </button>
                            </div>
                            <div className="grid grid-cols-3 gap-1.5 p-3">
                                {NAMA_BULAN_SINGKAT.map((bulan, idx) => (
                                    <button
                                        key={bulan}
                                        type="button"
                                        onClick={() => handleSelectMonth(idx)}
                                        className={cn(
                                            'rounded-lg py-2 text-center text-sm transition-colors',
                                            idx === selectedMonth && viewYear === selectedYear
                                                ? 'bg-neutral-900 font-semibold text-white dark:bg-neutral-100 dark:text-neutral-900'
                                                : idx === today.getMonth() && viewYear === today.getFullYear()
                                                  ? 'border border-neutral-400/40 font-semibold text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800'
                                                  : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800',
                                        )}
                                    >
                                        {bulan}
                                    </button>
                                ))}
                            </div>
                        </>
                    )}

                    {/* ── YEARS VIEW ── */}
                    {calView === 'years' && (
                        <>
                            <div className="flex items-center justify-between border-b border-neutral-200/40 px-3 py-2 dark:border-neutral-700/40">
                                <button
                                    type="button"
                                    onClick={prevYearRange}
                                    disabled={yearRangeStart <= MIN_YEAR}
                                    className="rounded-lg p-1.5 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-neutral-800"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>
                                <span className="text-sm font-semibold">
                                    {yearRangeStart} – {yearRangeStart + 11}
                                </span>
                                <button
                                    type="button"
                                    onClick={nextYearRange}
                                    className="rounded-lg p-1.5 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>
                            <div className="grid grid-cols-3 gap-1.5 p-3">
                                {Array.from({ length: 12 }, (_, i) => yearRangeStart + i).map((year) => (
                                    <button
                                        key={year}
                                        type="button"
                                        onClick={() => handleSelectYear(year)}
                                        className={cn(
                                            'rounded-lg py-2 text-center text-sm transition-colors',
                                            year === selectedYear
                                                ? 'bg-neutral-900 font-semibold text-white dark:bg-neutral-100 dark:text-neutral-900'
                                                : year === today.getFullYear()
                                                  ? 'border border-neutral-400/40 font-semibold text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800'
                                                  : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800',
                                        )}
                                    >
                                        {year}
                                    </button>
                                ))}
                            </div>
                        </>
                    )}
                </PopoverContent>
            </Popover>
        </>
    );
}
