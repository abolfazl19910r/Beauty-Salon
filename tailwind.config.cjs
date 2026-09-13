const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ["class"],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // These map straight to the CSS custom properties defined in resources/css/app.css's
            // @layer base block (:root / .dark) — kept, since that file's own `@apply border-border`
            // / `@apply bg-background text-foreground` rules resolve through these Tailwind color
            // names, not through any class="..." written directly in a Blade view. Confirmed by
            // actually running `npm run build`: removing this broke the CSS build outright with
            // "The `border-border` class does not exist."
            colors: {
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",
                ring: "hsl(var(--ring))",
                background: "hsl(var(--background))",
                foreground: "hsl(var(--foreground))",
                primary: {
                    DEFAULT: "hsl(var(--primary))",
                    foreground: "hsl(var(--primary-foreground))",
                },
                secondary: {
                    DEFAULT: "hsl(var(--secondary))",
                    foreground: "hsl(var(--secondary-foreground))",
                },
                destructive: {
                    DEFAULT: "hsl(var(--destructive))",
                    foreground: "hsl(var(--destructive-foreground))",
                },
                muted: {
                    DEFAULT: "hsl(var(--muted))",
                    foreground: "hsl(var(--muted-foreground))",
                },
                accent: {
                    DEFAULT: "hsl(var(--accent))",
                    foreground: "hsl(var(--accent-foreground))",
                },
                popover: {
                    DEFAULT: "hsl(var(--popover))",
                    foreground: "hsl(var(--popover-foreground))",
                },
                card: {
                    DEFAULT: "hsl(var(--card))",
                    foreground: "hsl(var(--card-foreground))",
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            // ⭐ Removed (proven unused, unlike everything above): the accordion-down/up,
            // fade-in/fade-out, and slide-in keyframes/animation entries that used to live here.
            // Tailwind would have exposed these as animate-fade-in / animate-slide-in / etc.
            // utility classes, but every Blade view that uses a `fade-in` class (dozens of admin
            // views) is actually referencing a completely separate, hand-written `.fade-in { }`
            // CSS rule defined directly in each layout's own <style> block — confirmed with a
            // project-wide grep — so these Tailwind-generated utilities were never actually
            // referenced anywhere. accordion-down/up in particular depended on
            // `--radix-accordion-content-height`, a CSS variable only ever set by the Radix
            // Accordion component this project no longer uses.
            spacing: {
                '128': '32rem',
                '144': '36rem',
            },
            zIndex: {
                '100': '100',
                'modal': '1000'
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        // ⭐ tailwindcss-animate removed — it only supplied Radix-specific `animate-in`/`animate-out`
        // style variants (`data-[state=open]:animate-in` etc.), confirmed unused by grepping for
        // any `animate-in`/`animate-out`/`data-[state=` usage across resources/views; nothing here
        // depends on it, and its whole purpose was pairing with the now-removed Radix components.
    ],
};
