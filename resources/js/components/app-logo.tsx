import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex size-10 shrink-0 items-center justify-center">
                <AppLogoIcon className="size-10" />
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left">
                <span className="truncate text-sm leading-tight font-bold tracking-[0.12em]">
                    SIMANTIK
                </span>
                <span className="truncate text-[10px] leading-tight text-muted-foreground">
                    BPS Kota Sawahlunto
                </span>
            </div>
        </>
    );
}
