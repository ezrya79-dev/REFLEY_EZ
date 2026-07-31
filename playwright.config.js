import { defineConfig, devices } from '@playwright/test';

/**
 * Configuration Playwright — tests E2E des parcours critiques de Refley.
 *
 * L'application est servie par `e2e/serve.sh` (base SQLite + seeder de démo),
 * ou réutilisée si elle tourne déjà (dév local). En CI, le serveur est démarré
 * à chaque run. L'URL de base est surchargée par APP_BASE_URL.
 */
const baseURL = process.env.APP_BASE_URL || 'http://127.0.0.1:8000';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 2 : undefined,
  reporter: process.env.CI
    ? [['list'], ['html', { open: 'never' }]]
    : [['list']],

  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    locale: 'fr-FR',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // Démarre l'application pour les tests, ou réutilise un serveur déjà lancé.
  webServer: {
    command: 'bash e2e/serve.sh',
    url: `${baseURL}/login`,
    timeout: 120_000,
    reuseExistingServer: !process.env.CI,
    stdout: 'pipe',
    stderr: 'pipe',
  },
});
