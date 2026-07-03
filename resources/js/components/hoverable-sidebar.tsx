import { AppSidebar } from '@/components/app-sidebar';
import { useProjectDrawer } from '@/components/project-drawer';
import { useSidebar } from '@/components/ui/sidebar';
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';

/**
 * Wraps AppSidebar to add hover-to-expand behaviour.
 * The sidebar can still be toggled manually (click trigger or ⌘B);
 * hovering always expands it, and leaving collapses it back to the last manual state.
 *
 * When the project drawer is open, the sidebar stays expanded while the mouse
 * is over either the sidebar or the drawer. Both collapse together when the
 * mouse leaves the combined area.
 */
export function HoverableSidebar({ children }: { children?: ReactNode }) {
    return (
        <HoverWrapper>
            {children ?? <AppSidebar />}
        </HoverWrapper>
    );
}

function HoverWrapper({ children }: { children: ReactNode }) {
    const { setOpen, open } = useSidebar();
    const { open: drawerOpen } = useProjectDrawer();
    const [manualOpen, setManualOpen] = useState(true);
    const isHovering = useRef(false);
    const drawerOpenRef = useRef(drawerOpen);
    drawerOpenRef.current = drawerOpen;

    // Sync manualOpen when the user toggles the sidebar (click or keyboard shortcut)
    // but ignore changes caused by our own hover handlers.
    useEffect(() => {
        if (!isHovering.current) {
            setManualOpen(open);
        }
    }, [open]);

    // When the project drawer closes, end the hover session and collapse
    // the sidebar back to its manual state. This handles the case where
    // the mouse leaves the drawer to the main content area.
    const prevDrawerOpen = useRef(drawerOpen);
    useEffect(() => {
        if (prevDrawerOpen.current && !drawerOpen) {
            // Drawer just closed — collapse sidebar to manual state
            isHovering.current = false;
            setOpen(manualOpen);
        }
        prevDrawerOpen.current = drawerOpen;
    }, [drawerOpen, setOpen, manualOpen]);

    const handleMouseEnter = useCallback(() => {
        isHovering.current = true;
        setOpen(true);
    }, [setOpen]);

    const handleMouseLeave = useCallback(() => {
        if (drawerOpenRef.current) {
            // Drawer is open — keep sidebar expanded.
            // When the mouse leaves the drawer, its onMouseLeave will close
            // the drawer, which triggers the effect above to collapse both.
            return;
        }
        isHovering.current = false;
        setOpen(manualOpen);
    }, [setOpen, manualOpen]);

    return (
        <div
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
            className="relative shrink-0"
        >
            {children}
        </div>
    );
}
