import { expect, browserPhp, loginAs, requireE2ePassword, test } from './fixtures';

const slug = 's5-deployment-rehearsal-disposable';
const enquiryName = 'S5 deployment rehearsal enquiry';

test.afterEach(() => {
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        $product = App\\Models\\CoffeeProduct::where('slug', '${slug}')->where('locale', 'en')->first();
        if ($product && $product->image) {
            Illuminate\\Support\\Facades\\Storage::disk('public')->delete($product->image);
        }
        App\\Models\\CoffeeProduct::where('slug', '${slug}')->delete();
        App\\Models\\Lead::where('name', '${enquiryName}')->delete();
    `);
});

test('fresh public catalog and sitemap render without server errors', async ({ page, request }) => {
    await page.goto('/coffee');
    await expect(page.getByRole('heading', { name: 'Coffee catalogue' })).toBeVisible();

    const response = await request.get('/sitemap.xml');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('<urlset');
});

test('admin login and product imagery render on the disposable browser database', async ({ page }) => {
    await page.goto('/admin/login');
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();

    await loginAs(page, 'admin@anduongiti.com', process.env.ADMIN_PASSWORD ?? requireE2ePassword());
    await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible();

    await page.goto('/coffee/robusta-scratch-18-bean-coffee');
    await expect(page.getByRole('img').first()).toBeVisible();
});

test('public enquiry persists to the disposable MySQL database', async ({ page }) => {
    await page.goto('/contact');
    await page.locator('#name').fill(enquiryName);
    await page.locator('#email').fill('s5.deployment.rehearsal@gmail.com');
    await page.locator('#message').fill('Disposable deployment rehearsal enquiry with sufficient sourcing detail.');
    await page.getByRole('button', { name: 'Send message' }).click();

    await expect(page).toHaveURL(/\/contact\?sent=1$/);
    const persisted = browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        echo App\\Models\\Lead::where('name', '${enquiryName}')->where('email', 's5.deployment.rehearsal@gmail.com')->exists() ? 'yes' : 'no';
    `).trim();
    expect(persisted).toBe('yes');
});

test('admin image upload creates a public coffee product', async ({ page }) => {
    await page.goto('/admin/login');
    await loginAs(page, 'admin@anduongiti.com', process.env.ADMIN_PASSWORD ?? requireE2ePassword());

    await page.goto('/admin/coffee-products/create');
    await page.getByLabel('Locale').selectOption('en');
    await page.getByLabel('Category').selectOption({ label: 'Green coffee beans' });
    await page.getByLabel('Bean variety').selectOption('robusta');
    await page.getByLabel('Slug').fill(slug);
    await page.getByLabel('Title').fill('S5 deployment rehearsal product');
    await page.getByLabel('Description').fill('Disposable product created by the committed deployment rehearsal spec.');

    const uploaded = page.waitForResponse((response) =>
        response.url().includes('/livewire/upload-file') && response.status() === 200,
    );
    await page.locator('input[type="file"]').setInputFiles('public/brand/og-default.png');
    await uploaded;
    await page.getByLabel('Image alt').fill('Approved brand image used for deployment rehearsal upload.');

    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(page.getByText('Created', { exact: false })).toBeVisible();

    const stored = browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        $product = App\\Models\\CoffeeProduct::where('slug', '${slug}')->where('locale', 'en')->first();
        echo $product && str_starts_with((string) $product->image, 'coffee/') ? 'yes' : 'no';
    `).trim();
    expect(stored).toBe('yes');

    await page.goto(`/coffee/${slug}`);
    await expect(page.getByRole('img').first()).toBeVisible();
});
