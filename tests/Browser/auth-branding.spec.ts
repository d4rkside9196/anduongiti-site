import { existsSync } from 'node:fs';
import { expect, expectNoSeriousA11yViolations, test } from './fixtures';

const assets = [
    { path: 'public/brand/favicon.svg', url: '/brand/favicon.svg', mime: /image\/svg\+xml/ },
    { path: 'public/brand/apple-touch-icon.png', url: '/brand/apple-touch-icon.png', mime: /image\/png/ },
    { path: 'public/brand/og-default.png', url: '/brand/og-default.png', mime: /image\/png/ },
];

for (const asset of assets) {
    test(`${asset.path} exists and is served with the correct MIME type`, async ({ request }) => {
        expect(existsSync(asset.path)).toBe(true);
        const response = await request.get(asset.url);
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toMatch(asset.mime);
    });
}

test('public layout references the favicon, touch icon, and social image', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('link[rel="icon"][href="/brand/favicon.svg"]')).toHaveCount(1);
    await expect(page.locator('link[rel="apple-touch-icon"][href="/brand/apple-touch-icon.png"]')).toHaveCount(1);
    await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', /\/brand\/og-default\.png$/);
    await expect(page.locator('meta[property="og:image:width"]')).toHaveAttribute('content', '1200');
    await expect(page.locator('meta[property="og:image:height"]')).toHaveAttribute('content', '630');
    await expect(page.locator('meta[name="twitter:card"]')).toHaveAttribute('content', 'summary_large_image');
    await expect(page.locator('meta[name="twitter:image"]')).toHaveAttribute('content', /\/brand\/og-default\.png$/);
});

test('Filament login uses the approved An Duong ITI brand', async ({ page }) => {
    await page.goto('/admin/login');

    await expect(page.locator('img[src="/anduong-logo-dark.png"][alt="An Duong ITI"]')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
    await expect(page.locator('img[src="/anduong-logo-light.png"]')).toBeHidden();
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
    await expect(page.getByText('Welcome to Laravel', { exact: false })).toHaveCount(0);
});

test('Filament login uses the light approved logo in dark mode', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.goto('/admin/login');

    await expect(page.locator('img[src="/anduong-logo-dark.png"]')).toBeHidden();
    await expect(page.locator('img[src="/anduong-logo-light.png"][alt*="An Duong ITI"]')).toBeVisible();
});

test('Filament login has no serious accessibility violations', async ({ page }) => {
    await page.goto('/admin/login');
    await expectNoSeriousA11yViolations(page);
});
