The frontend aesthetic style is built on **Tailwind CSS v4** combined with the **shadcn/ui** component library, following the "New York" style variant. This setup provides a utility-first CSS methodology with a robust set of pre-built, accessible React components.

### Core Styling Architecture
- **CSS Framework**: Tailwind CSS v4 is used via the `@tailwindcss/vite` plugin. The primary stylesheet (`resources/css/app.css`) imports Tailwind and defines custom theme variables using the `@theme` directive.
- **Design Tokens**: The design system relies heavily on CSS custom properties (variables) for colors, radii, and fonts. These are defined in `:root` and `.dark` scopes within `app.css`, allowing for seamless light/dark mode switching. Key tokens include `--background`, `--foreground`, `--primary`, `--card`, `--sidebar`, and `--radius`.
- **Color Palette**: The palette uses OKLCH color spaces for perceptual uniformity. It features a neutral base with specific semantic roles (e.g., `destructive`, `muted`, `accent`).
- **Typography**: The default font family is 'Instrument Sans', falling back to system UI fonts.

### Component Library & Conventions
- **shadcn/ui**: The project uses shadcn/ui for its UI components (Button, Input, Card, Dialog, etc.). These are located in `resources/js/components/ui/`. 
- **Variant Management**: Components use `class-variance-authority` (CVA) to manage conditional styles (variants and sizes). For example, the `Button` component defines `default`, `destructive`, `outline`, `secondary`, `ghost`, and `link` variants.
- **Class Merging**: The `cn` utility from `resources/js/lib/utils.ts` combines `clsx` and `tailwind-merge` to safely merge Tailwind classes without conflicts, a standard pattern in shadcn/ui projects.
- **Icons**: `lucide-react` is the designated icon library, integrated via the `Icon` component and used throughout the UI.

### Theming & Dark Mode
- **Strategy**: Dark mode is implemented via a `.dark` class on the `<html>` element. The `useAppearance` hook manages the state ('light', 'dark', 'system') and persists it in `localStorage` and a cookie for SSR consistency.
- **Implementation**: The `HandleAppearance` middleware in Laravel reads the cookie to ensure the server-rendered initial HTML matches the client's theme preference, preventing flash-of-unstyled-content (FOUC).

### Developer Rules
1. **Use Design Tokens**: Always reference theme variables (e.g., `bg-background`, `text-primary`) instead of hardcoding colors. This ensures consistency and automatic dark mode support.
2. **Extend via `app.css`**: Add new custom theme values in the `@theme` block of `resources/css/app.css` rather than creating arbitrary Tailwind values where possible.
3. **Component Variants**: When creating new UI components, use `cva` to define variants and `cn` to merge classes, maintaining consistency with the existing shadcn/ui patterns.
4. **Responsive Design**: Leverage Tailwind's responsive prefixes (e.g., `md:flex`, `lg:w-1/2`) for layout adjustments. The system is mobile-first.