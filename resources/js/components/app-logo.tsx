import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-[34px] items-center justify-center rounded-[10px] bg-primary font-serif text-lg font-semibold text-primary-foreground">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate font-serif text-[15px] leading-tight font-medium">
                    ScholarGraph
                </span>
            </div>
        </>
    );
}
