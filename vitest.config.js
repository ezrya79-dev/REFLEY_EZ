import { defineConfig } from 'vitest/config';

/**
 * Tests unitaires du JavaScript client (logique pure des composants Alpine).
 * Environnement jsdom pour localStorage / window sans navigateur réel.
 */
export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        globals: true,
    },
});
