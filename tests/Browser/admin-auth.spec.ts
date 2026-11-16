import { expect, test } from './fixtures';

const email = process.env.ADMIN_EMAIL ?? 'admin@anduongiti.com';
const password = process.env.ADMIN_PASSWORD;

test.beforeEach(() => {
    test.skip(!password, 'Set ADMIN_PASSWORD for the disposable browser database.');
});

test('admin can sign in and log out without exposing the password', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email address').fill(email!);
    await page.locator('input[type="password"]').fill(password!);
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('input[type="password"]')).toHaveCount(0);

    await page.getByRole('button', { name: 'User menu' }).click();
    await page.getByRole('button', { name: 'Sign out' }).click();
    await expect(page).toHaveURL(/\/admin\/login$/);
});

test('wrong password shows a generic authentication error', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email address').fill(email!);
    await page.locator('input[type="password"]').fill('incorrect-browser-password');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByText('These credentials do not match our records.')).toBeVisible();
    await expect(page).toHaveURL(/\/admin\/login$/);
});

test('forgot password form is available from login', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('link', { name: 'Forgot password?' }).click();

    await expect(page).toHaveURL(/\/admin\/password-reset\/request$/);
    await expect(page.getByLabel('Email address')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Send email' })).toBeVisible();
});

test('profile exposes password change with current-password confirmation', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email address').fill(email!);
    await page.locator('input[type="password"]').fill(password!);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL('**/admin');
    await page.goto('/admin/profile');

    await expect(page.getByLabel('Name')).toBeVisible();
    await expect(page.getByLabel('New password')).toBeVisible();
    await expect(page.getByLabel('Current password')).toBeHidden();
    await page.getByLabel('New password').fill('NotSavedBrowser1!Password');
    await expect(page.getByLabel('Current password')).toBeVisible();
});
