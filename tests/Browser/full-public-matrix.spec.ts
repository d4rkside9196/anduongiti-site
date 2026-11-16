import { expect, browserPhp, test } from './fixtures';

const pages = [
    { path: '/', heading: /Vietnamese|agricultural|Coffee/i },
    { path: '/about', heading: /About|An Duong/i },
    { path: '/products', heading: /Products|Coffee|Rubber/i },
    { path: '/products/coffee', heading: /Coffee/i },
    { path: '/products/rubber', heading: /rubber/i },
    { path: '/coffee', heading: /Coffee/i },
    { path: '/coffee/robusta-scratch-18-bean-coffee', heading: /Robusta Scratch 18/i },
    { path: '/vi/coffee', heading: /Cà phê/i },
    { path: '/support', heading: /Support|support/i },
    { path: '/vi/support', heading: /Hỗ trợ|hỗ trợ/i },
    { path: '/news', heading: /News|Insights|Journal/i },
    { path: '/contact', heading: /partnership/i },
] as const;

let projectName = '';
let fallbackSlug = '';

test.beforeAll(() => {
    projectName = test.info().project.name;
    fallbackSlug = `s5-language-fallback-${projectName}`;
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        $category = App\\Models\\ProductCategory::where('slug', 'beans')->where('locale', 'en')->firstOrFail();
        App\\Models\\CoffeeProduct::updateOrCreate(
            ['slug' => '${fallbackSlug}', 'locale' => 'en'],
            ['category_id' => $category->id, 'bean_variety' => 'robusta', 'title' => 'S5 fallback probe', 'description' => 'Disposable browser translation fallback probe.', 'visible' => true]
        );
    `);
});

test.beforeEach(async ({ page }) => {
    // Disable reveal/scroll animations so images and submit buttons stay
    // stable while Playwright measures or clicks them.
    await page.emulateMedia({ reducedMotion: 'reduce' });
});

test.afterAll(() => {
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        App\\Models\\CoffeeProduct::where('slug', '${fallbackSlug}')->delete();
        App\\Models\\Lead::where('name', 'S5 matrix disposable enquiry')->delete();
    `);
});

for (const { path, heading } of pages) {
    test(`${path} has complete responsive public content`, async ({ page }) => {
        const response = await page.goto(path);
        expect(response?.status()).toBe(200);
        await expect(page.getByRole('main')).toBeVisible();
        await expect(page.getByRole('main').getByRole('heading', { name: heading }).first()).toBeVisible();

        const rawKeys = await page.locator('body').textContent();
        expect(rawKeys).not.toMatch(/\b(?:site|auth|validation|pagination)\.[a-z0-9_.-]+/);

        const dimensions = await page.evaluate(() => ({
            content: document.documentElement.scrollWidth,
            viewport: document.documentElement.clientWidth,
        }));
        expect(dimensions.content).toBeLessThanOrEqual(dimensions.viewport);

        for (const image of await page.getByRole('main').locator('img').all()) {
            await image.scrollIntoViewIfNeeded();
            await expect(image).toBeVisible();
            await expect.poll(() => image.evaluate((element: HTMLImageElement) => element.complete && element.naturalWidth > 0)).toBe(true);
        }

        const canonical = page.locator('link[rel="canonical"]');
        const canonicalUrl = await canonical.getAttribute('href');
        expect(canonicalUrl).not.toBeNull();
        expect(new URL(canonicalUrl!).pathname).toBe(path);
        await expect(page.locator('link[rel="alternate"][hreflang="en"]')).toHaveCount(1);
        await expect(page.locator('link[rel="alternate"][hreflang="vi"]')).toHaveCount(1);
        await expect(page.locator('link[rel="alternate"][hreflang="x-default"]')).toHaveCount(1);
    });
}

test('sitemap is successful XML without raw translation keys', async ({ request }) => {
    const response = await request.get('/sitemap.xml');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toMatch(/xml/);
    const body = await response.text();
    expect(body).toContain('<urlset');
    expect(body).toContain('/coffee');
    expect(body).not.toMatch(/\bsite\.[a-z0-9_.-]+/);
});

test('category filtering preserves matching products and marks the URL noindex', async ({ page }) => {
    const response = await page.goto('/coffee?category=beans');
    expect(response?.status()).toBe(200);
    expect(response?.headers()['x-robots-tag']).toBe('noindex,follow');
    await expect(page.getByRole('heading').getByRole('link', { name: 'Robusta Scratch 18 Bean Coffee', exact: true })).toBeVisible();
    await expect(page.getByRole('heading').getByRole('link', { name: 'Arabica Cầu Đất Ground 250 g', exact: true })).toHaveCount(0);
});

test('missing translation falls back to the localized catalog', async ({ page }) => {
    await page.goto(`/coffee/${fallbackSlug}`);
    await expect(page.getByRole('heading', { name: 'S5 fallback probe', exact: true })).toBeVisible();
    const vietnamese = page.locator('link[rel="alternate"][hreflang="vi"]');
    await expect(vietnamese).toHaveAttribute('href', /\/vi\/coffee$/);
});

test('invalid and valid contact submissions retain accessible feedback', async ({ page }) => {
    await page.goto('/contact');
    const form = page.locator('form');
    await form.evaluate((element) => { element.noValidate = true; });
    await page.getByRole('button', { name: 'Send message' }).click();
    await expect(page.getByRole('alert')).toBeFocused();
    await expect(page.locator('#name')).toHaveAttribute('aria-invalid', 'true');

    const email = `s5.matrix.${projectName.replace(/[^a-z0-9]/g, '')}@gmail.com`;
    await page.goto('/contact');
    await page.locator('#name').fill('S5 matrix disposable enquiry');
    await page.locator('#email').fill(email);
    await page.locator('#message').fill('Disposable release matrix enquiry with sufficient sourcing detail.');
    await page.getByRole('button', { name: 'Send message' }).click();
    await expect(page).toHaveURL(/\/contact\?sent=1$/);
    await expect(page.getByRole('status')).toContainText('Thank you!');
});

test('mobile keyboard navigation and reduced motion remain usable', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');
    const trigger = page.getByRole('button', { name: 'Open menu' });
    await trigger.focus();
    await trigger.press('Enter');
    await expect(page.locator('#mobile-menu')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(trigger).toBeFocused();
    for (const section of await page.locator('main section').all()) {
        await expect(section).toHaveCSS('opacity', '1');
    }
});
