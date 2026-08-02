import { expect, test } from '@playwright/test';

/**
 * Parcours critiques uniquement (le reste est couvert par la suite Pest) :
 * le site public répond, et un compte peut se connecter puis se déconnecter.
 */

test('le site public est accessible sans compte', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('h1')).toBeVisible();

    await page.goto('/mentions-legales');
    await expect(page.locator('h1')).toBeVisible();
});

test('un utilisateur se connecte, atteint son espace, puis se déconnecte', async ({ page }) => {
    await page.goto('/login');

    await page.fill('#email', 'admin@demo.test');
    await page.fill('#password', 'Demo-Password-1234');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/tableau-de-bord/);

    // La barre latérale d'un administrateur expose l'administration des comptes.
    await expect(page.locator('.sidebar')).toContainText('Utilisateurs');

    await page.click('form[action$="/logout"] button');
    await expect(page).toHaveURL(/login/);
});

test('des identifiants invalides sont refusés', async ({ page }) => {
    await page.goto('/login');

    await page.fill('#email', 'admin@demo.test');
    await page.fill('#password', 'mauvais-mot-de-passe');
    await page.click('button[type="submit"]');

    await expect(page.locator('.field-error')).toBeVisible();
});
