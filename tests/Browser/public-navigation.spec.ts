import { expect, test } from './fixtures';

test.use({ viewport: { width: 360, height: 800 } });

test('mobile menu opens and closes by keyboard and closes on Escape', async ({ page }) => {
    await page.goto('/');

    const trigger = page.getByRole('button', { name: 'Open menu' });
    const menu = page.locator('#mobile-menu');

    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(trigger).toHaveAttribute('aria-controls', 'mobile-menu');
    await expect(menu).toBeHidden();

    await trigger.focus();
    await trigger.press('Enter');
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(menu).toBeVisible();
    await expect(menu.getByRole('link').first()).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(menu).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.press('Space');
    await expect(menu).toBeVisible();
    await trigger.press('Space');
    await expect(menu).toBeHidden();

    await trigger.press('Enter');
    await expect(menu).toBeVisible();
    await page.locator('main').click({ position: { x: 5, y: 5 } });
    await expect(menu).toBeHidden();
});

test('contact social actions meet the minimum touch target', async ({ page }) => {
    await page.goto('/contact');

    const socialLinks = page.locator('aside a[aria-label="Facebook"], aside a[aria-label="LinkedIn"], aside a[aria-label="Shopee"], aside a[aria-label="Alibaba"]');
    expect(await socialLinks.count()).toBeGreaterThan(0);
    for (const link of await socialLinks.all()) {
        const box = await link.boundingBox();
        expect(box).not.toBeNull();
        expect(box!.width).toBeGreaterThanOrEqual(44);
        expect(box!.height).toBeGreaterThanOrEqual(44);
    }
});

test('shared landmarks and quick-contact links are keyboard accessible', async ({ page }) => {
    await page.goto('/');

    const skipLinks = page.locator('a[href="#main-content"]');
    await expect(skipLinks).toHaveCount(1);
    await skipLinks.focus();
    await expect(skipLinks).toBeVisible();
    await skipLinks.press('Enter');
    await expect(page.locator('main#main-content')).toBeFocused();

    const quickContact = page.getByRole('navigation', { name: 'Quick contact' });
    await expect(quickContact).toBeVisible();
    for (const link of await quickContact.getByRole('link').all()) {
        await expect(link).toHaveAccessibleName(/\S/);
        const box = await link.boundingBox();
        expect(box).not.toBeNull();
        expect(box!.width).toBeGreaterThanOrEqual(44);
        expect(box!.height).toBeGreaterThanOrEqual(44);
    }
});
