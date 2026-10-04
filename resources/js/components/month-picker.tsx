import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import CalendarDays from 'lucide-react/icons/calendar-days';
import ChevronLeft from 'lucide-react/icons/chevron-left';
import ChevronRight from 'lucide-react/icons/chevron-right';
import { useMemo, useState } from 'react';

const MONTHS = [
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

interface MonthPickerProps {
    value: string;
    onValueChange: (value: string) => void;
    minYear?: number;
    maxYear?: number;
    disabled?: boolean;
    className?: string;
}

export function MonthPicker({
    value,
    onValueChange,
    minYear = 2021,
    maxYear = new Date().getFullYear() + 1,
    disabled = false,
    className = '',
}: MonthPickerProps) {
    const [open, setOpen] = useState(false);
    const [yearValue, monthValue] = value.split('-').map(Number);
    const selectedYear = Number.isFinite(yearValue)
        ? yearValue
        : new Date().getFullYear();
    const selectedMonth = Number.isFinite(monthValue) ? monthValue : 1;
    const [displayYear, setDisplayYear] = useState(selectedYear);

    const label = useMemo(
        () => `${MONTHS[selectedMonth - 1] ?? MONTHS[0]} ${selectedYear}`,
        [selectedMonth, selectedYear],
    );

    const choose = (month: number) => {
        onValueChange(
            `${displayYear}-${String(month).padStart(2, '0')}`,
        );
        setOpen(false);
    };

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (next) setDisplayYear(selectedYear);
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={`justify-start gap-2 font-medium ${className}`}
                >
                    <CalendarDays className="size-4 text-muted-foreground" />
                    {label}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80 p-3">
                <div className="mb-3 flex items-center justify-between">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            setDisplayYear((year) =>
                                Math.max(minYear, year - 1),
                            )
                        }
                        disabled={displayYear <= minYear}
                        aria-label="Tahun sebelumnya"
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <div className="text-sm font-semibold">{displayYear}</div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            setDisplayYear((year) =>
                                Math.min(maxYear, year + 1),
                            )
                        }
                        disabled={displayYear >= maxYear}
                        aria-label="Tahun berikutnya"
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
                <div className="grid grid-cols-3 gap-1.5">
                    {MONTHS.map((month, index) => {
                        const monthNumber = index + 1;
                        const active =
                            displayYear === selectedYear &&
                            monthNumber === selectedMonth;

                        return (
                            <Button
                                key={month}
                                type="button"
                                variant={active ? 'default' : 'ghost'}
                                size="sm"
                                className="h-9 text-xs"
                                onClick={() => choose(monthNumber)}
                            >
                                {month.slice(0, 3)}
                            </Button>
                        );
                    })}
                </div>
            </PopoverContent>
        </Popover>
    );
}
