import { expect, test } from '@playwright/test';

test.use({
    viewport: { width: 360, height: 800 },
});

test('contact page does not overflow the mobile viewport', async ({ page }) => {
    await page.goto('/contact');
    await page.waitForLoadState('domcontentloaded');

    const width = await page.evaluate(() => ({
        content: document.documentElement.scrollWidth,
        viewport: document.documentElement.clientWidth,
    }));

    expect(width.content).toBeLessThanOrEqual(width.viewport);
});

test('quick contact stays in flow on mobile and fixed on desktop', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');

    const mobile = await page.evaluate(() => {
        const quickContact = document.querySelector('[aria-label="Quick contact"]');
        const footer = document.querySelector('footer');

        return {
            position: getComputedStyle(quickContact).position,
            quickContactTop: quickContact.getBoundingClientRect().top,
            footerBottom: footer.getBoundingClientRect().bottom,
        };
    });

    expect(mobile.position).toBe('static');
    expect(mobile.quickContactTop).toBeGreaterThanOrEqual(mobile.footerBottom);

    await page.setViewportSize({ width: 1440, height: 900 });

    await expect(page.locator('[aria-label="Quick contact"]')).toHaveCSS('position', 'fixed');
});

test('mobile menu handles Enter keydown and standard Space activation', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('domcontentloaded');

    const button = page.getByRole('button', { name: 'Open menu' });
    const menu = page.locator('#mobile-menu');

    await button.evaluate((element) => {
        element.dispatchEvent(new KeyboardEvent('keydown', {
            key: 'Enter',
            bubbles: true,
            cancelable: true,
        }));
    });

    await expect(menu).toBeVisible();
    await expect(menu.getByRole('link', { name: 'Products', exact: true })).toBeVisible();

    await button.press('Space');
    await expect(menu).toBeHidden();

    await button.press('Enter');
    await expect(menu).toBeVisible();

    await button.press('Enter');
    await expect(menu).toBeHidden();
});
