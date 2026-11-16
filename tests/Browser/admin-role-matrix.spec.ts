import { expect, browserPhp, loginAs, requireE2ePassword, test, type Page } from './fixtures';

type Role = 'super_admin' | 'catalog_manager' | 'support';

let role: Role;
let email = '';
let productTitle = '';
const password = requireE2ePassword();

const allowed: Record<Role, string[]> = {
    super_admin: ['/admin', '/admin/coffee-products', '/admin/leads', '/admin/users', '/admin/activity-logs', '/admin/settings'],
    catalog_manager: ['/admin', '/admin/coffee-products', '/admin/products', '/admin/product-categories'],
    support: ['/admin', '/admin/leads'],
};
const denied: Record<Role, string[]> = {
    super_admin: [],
    catalog_manager: ['/admin/leads', '/admin/users', '/admin/activity-logs', '/admin/settings'],
    support: ['/admin/coffee-products', '/admin/products', '/admin/users', '/admin/activity-logs', '/admin/settings'],
};

test.beforeAll(() => {
    role = test.info().project.metadata.role as Role;
    email = `s5-${role.replace('_', '-')}@browser.test`;
    productTitle = `S5 ${role} disposable catalog product`;
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        App\\Models\\User::updateOrCreate(
            ['email' => '${email}'],
            ['name' => 'S5 ${role}', 'email_verified_at' => now(), 'password' => getenv('E2E_TEST_PASSWORD'), 'role' => '${role}', 'is_active' => true]
        );
    `);
});

test.afterAll(() => {
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        App\\Models\\CoffeeProduct::where('title', '${productTitle}')->orWhere('title', '${productTitle} edited')->delete();
        App\\Models\\User::where('email', '${email}')->delete();
    `);
});

async function openAdmin(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await loginAs(page, email, password);
}

test('allowed and denied destinations enforce the role matrix', async ({ page }) => {
    await openAdmin(page);
    for (const path of allowed[role]) {
        const response = await page.goto(path);
        expect(response?.status(), `${role} must open ${path}`).toBe(200);
        await expect(page.getByRole('main')).toBeVisible();
    }
    for (const path of denied[role]) {
        const response = await page.goto(path);
        expect(response?.status(), `${role} must be denied ${path}`).toBe(403);
    }
});

test('catalog roles create, edit, and translate only marked records', async ({ page }) => {
    test.skip(role === 'support', 'Support has no catalog workflow.');
    await openAdmin(page);
    await page.goto('/admin/coffee-products/create');
    await page.getByLabel('Locale').selectOption('en');
    await page.getByLabel('Category').selectOption({ label: 'Green coffee beans' });
    await page.getByLabel('Bean variety').selectOption('robusta');
    await page.getByLabel('Slug').fill(`s5-${role.replace('_', '-')}-disposable`);
    await page.getByLabel('Title').fill(productTitle);
    await page.getByLabel('Description').fill('Disposable browser catalog workflow record.');
    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(page.getByText('Created', { exact: false })).toBeVisible();

    await page.goto('/admin/coffee-products');
    const row = page.getByRole('row', { name: new RegExp(productTitle) });
    await row.getByRole('link', { name: 'Edit' }).click();
    await page.getByLabel('Title').fill(`${productTitle} edited`);
    await page.getByRole('button', { name: 'Save changes' }).click();
    await expect(page.getByText('Saved', { exact: false })).toBeVisible();

    await page.goto('/admin/coffee-products');
    const editedRow = page.getByRole('row', { name: new RegExp(`${productTitle} edited`) });
    await editedRow.getByRole('button', { name: 'Duplicate to Vietnamese' }).click();
    await expect(page.getByText('Translation draft created')).toBeVisible();
    const translated = browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        echo App\\Models\\CoffeeProduct::where('slug', 's5-${role.replace('_', '-')}-disposable')->where('locale', 'vi')->where('visible', false)->exists() ? 'yes' : 'no';
    `).trim();
    expect(translated).toBe('yes');
});
