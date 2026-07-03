import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col bg-secondary p-10 text-secondary-foreground lg:flex dark:border-r">
                <Link
                    href={home()}
                    className="relative z-20 flex items-center font-serif text-lg font-medium"
                >
                    <div className="mr-2 flex size-[34px] items-center justify-center rounded-[10px] bg-primary font-serif text-primary-foreground">
                        <AppLogoIcon className="size-6 fill-current text-white dark:text-black" />
                    </div>
                    {name}
                </Link>
            </div>
            <div className="w-full bg-background lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <div className="flex size-[34px] items-center justify-center rounded-[10px] bg-primary">
                            <AppLogoIcon className="size-6 fill-current text-white dark:text-black" />
                        </div>
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="font-serif text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
