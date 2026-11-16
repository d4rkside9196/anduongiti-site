import { expect, expectNoSeriousA11yViolations, test } from './fixtures';

for (const path of ['/', '/contact']) {
    test(`${path} has one main landmark and a working skip link`, async ({ page }) => {
        await page.goto(path);

        const main = page.getByRole('main');
        const skipLink = page.getByRole('link', { name: 'Skip to main content' });

        await expect(main).toHaveCount(1);
        await expect(main).toBeVisible();
        await expect(skipLink).toHaveCount(1);
        await skipLink.focus();
        await expect(skipLink).toBeVisible();
        await skipLink.press('Enter');
        await expect(main).toBeFocused();
    });
}

for (const path of ['/', '/coffee', '/vi/coffee', '/support', '/contact']) {
    test(`${path} has no serious accessibility violations`, async ({ page }) => {
        await page.goto(path);
        await expectNoSeriousA11yViolations(page);
    });
}

test('reduced motion leaves reveal content visible', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');

    const sections = page.locator('main section');
    await expect(sections.first()).toBeVisible();
    for (const section of await sections.all()) {
        await expect(section).toHaveCSS('opacity', '1');
        await expect(section).toHaveCSS('visibility', 'visible');
    }
});

test('form primitives expose accessible labels and a visible focus ring', async ({ page }) => {
    await page.goto('/contact');

    const form = page.locator('form');
    for (const label of [
        'Full name',
        'Company',
        'Email',
        'Country',
        "Tell us about your business and what you're sourcing",
    ]) {
        await expect(form.getByRole('textbox', { name: label, exact: true })).toBeVisible();
    }
    await expect(form.getByRole('combobox', { name: 'Product of interest', exact: true })).toBeVisible();

    const name = form.getByRole('textbox', { name: 'Full name', exact: true });
    await name.focus();
    const focusStyle = await name.evaluate((element) => {
        const style = getComputedStyle(element);
        return { outlineStyle: style.outlineStyle, outlineWidth: style.outlineWidth, boxShadow: style.boxShadow };
    });
    expect(
        focusStyle.outlineStyle !== 'none' && Number.parseFloat(focusStyle.outlineWidth) >= 2
            || focusStyle.boxShadow !== 'none',
    ).toBe(true);
});

test('form primitives connect field errors and announce success', async ({ page }) => {
    await page.goto('/contact');
    await page.locator('form').evaluate((form) => { form.noValidate = true; });
    await page.getByRole('button', { name: 'Send message' }).click();

    const name = page.locator('form').getByRole('textbox', { name: 'Full name', exact: true });
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(name).toHaveAttribute('aria-invalid', 'true');
    await expect(name).toHaveAttribute('aria-describedby', 'name-error');
    await expect(page.locator('#name-error')).toBeVisible();

    await page.goto('/contact?sent=1');
    await expect(page.getByRole('status')).toBeVisible();
    await expect(page.getByRole('status')).toContainText('Thank you!');
});

test('invalid contact submission focuses a linked summary and preserves input', async ({ page }) => {
    await page.goto('/contact');
    const form = page.locator('form');
    await form.evaluate((element) => { element.noValidate = true; });
    await form.getByRole('textbox', { name: 'Full name', exact: true }).fill('Jane Browser');
    await form.getByRole('textbox', { name: 'Email', exact: true }).fill('not-an-email');
    await form.getByRole('textbox', { name: "Tell us about your business and what you're sourcing", exact: true })
        .fill('We need product documentation and samples for our roasting business.');
    await page.getByRole('button', { name: 'Send message' }).click();

    const summary = page.getByRole('alert');
    await expect(summary).toBeFocused();
    await expect(summary.getByRole('link')).toHaveCount(1);
    await expect(summary.getByRole('link')).toHaveAttribute('href', '#email');
    await expect(page.locator('#email-error')).toBeVisible();
    await expect(page.locator('#email')).toHaveAttribute('aria-describedby', 'email-error');
    await expect(page.locator('#name')).toHaveValue('Jane Browser');
    await expect(page.locator('#email')).toHaveValue('not-an-email');
    await expect(page.locator('#message')).toHaveValue('We need product documentation and samples for our roasting business.');
});

test('invalid source slug links to a usable field and clears the stale hidden value', async ({ page }) => {
    await page.goto('/contact?product=robusta-scratch-18-bean-coffee');
    await page.locator('#name').fill('Source Browser');
    await page.locator('#email').fill('source.browser@gmail.com');
    await page.locator('#message').fill('Please send product documentation and wholesale sample details.');
    await page.locator('input[name="source_slug"]').evaluate((input) => { input.value = 'missing-sku'; });
    await page.getByRole('button', { name: 'Send message' }).click();

    const sourceLink = page.getByRole('alert').getByRole('link');
    await expect(sourceLink).toHaveAttribute('href', '#product');
    await expect(page.locator('#source_slug-error')).toBeVisible();
    await expect(page.locator('#product')).toHaveAttribute('aria-describedby', 'source_slug-error');
    await expect(page.locator('input[name="source_slug"]')).toHaveCount(0);

    await sourceLink.click();
    await expect(page.locator('#product')).toBeFocused();
});

test('empty contact submission exposes field errors and valid submission announces success', async ({ page }) => {
    await page.goto('/contact');
    const form = page.locator('form');
    await form.evaluate((element) => { element.noValidate = true; });
    await page.getByRole('button', { name: 'Send message' }).click();

    await expect(page.getByRole('alert')).toBeFocused();
    for (const field of ['name', 'email', 'message']) {
        await expect(page.locator(`#${field}`)).toHaveAttribute('aria-invalid', 'true');
        await expect(page.locator(`#${field}-error`)).toBeVisible();
    }

    await page.goto('/contact');
    await page.locator('#name').fill('S1 Browser');
    await page.locator('#email').fill('s1.browser@gmail.com');
    await page.locator('#message').fill('Please send wholesale product documentation and sample details.');
    await page.getByRole('button', { name: 'Send message' }).click();

    await expect(page).toHaveURL(/\/contact\?sent=1$/);
    await expect(page.getByRole('status')).toBeVisible();
    await expect(page.getByRole('status')).toContainText('Thank you!');
});
