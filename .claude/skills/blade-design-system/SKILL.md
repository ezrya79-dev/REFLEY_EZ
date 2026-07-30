---
name: blade-design-system
description: Set up a hand-rolled CSS design system with Blade components, light/dark themes and FR/EN i18n for a Laravel app — design tokens as CSS variables, an accent color pipeline, reusable UI components and a translation structure. Use this whenever the user starts the UI of a new Laravel/Blade app, asks for consistent styling, dark mode, a component library, bilingual interface, or wants the app to "look like OrthoZ" without Tailwind or a heavy UI framework.
---

# Blade design system

A single `design-system.css` owns every visual decision; Blade components consume it; pages assemble components. No utility-class framework — the design system **is** the vocabulary, which keeps markup readable and redesigns cheap (change tokens, not templates).

## Method

1. **Tokens first** — define CSS custom properties on `:root`: color scales (surface, text, borders, accent + semantic success/warn/danger), spacing steps, radii, shadows, type scale. The accent comes from settings (`app-settings-branding`): presets via `[data-accent="…"]` selectors, custom hex via an inline `--accent` override, variants derived with `color-mix()`.
2. **Dark mode as a first-class theme** — a `data-theme="dark"` attribute on `<html>` (user preference from the profile module, default from `prefers-color-scheme`). Only tokens change between themes; component rules never mention colors directly. Test every component in both themes as you build it, not at the end.
3. **Blade components** (`resources/views/components/`) — build the core kit: layout (sidebar/topbar), card, button variants, form fields with error slots, table with sortable headers, badge, modal, tabs, empty-state, toast. Each component: props for variants, slots for content, zero page-specific logic.
4. **Interactivity via Alpine.js** — sprinkle behavior (menus, modals, tabs) as `x-data` in components; anything heavier than a sprinkle becomes a dedicated JS module bundled by Vite.
5. **i18n structure** — `lang/fr/…` + `lang/en/…` from day one, `__('…')` everywhere, primary locale complete and fallback configured. UI copy in templates is a bug even in a "single-language" app — the second language always arrives.
6. **Print styles** where the domain needs paper (labels, daily digests): a dedicated `@media print` section in the design system, not per-page hacks.

## Verification checklist

- [ ] No hex colors outside `design-system.css` (grep `#[0-9a-f]{3,6}` in views)
- [ ] Every component renders correctly in light AND dark
- [ ] Locale switch translates the full shell (no hardcoded strings)
- [ ] Accent change in settings recolors the app with no CSS edit

## Reference implementation

OrthoZ: `resources/css/design-system.css`, `resources/views/components/`, `lang/fr` + `lang/en`. Copy the CSS file as a starting point when building for the same team — it encodes their tastes (spacing, radii, contrast) better than a rewrite.
