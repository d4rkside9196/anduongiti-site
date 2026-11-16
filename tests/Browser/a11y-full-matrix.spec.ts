import { expect, browserPhp, expectNoSeriousA11yViolations, loginAs, requireE2ePassword, test } from './fixtures';

const email = 's5-a11y-super-admin@browser.test';
const password = requireE2ePassword();
const leadName = 'S5 axe disposable lead';

for (const path of ['/', '/coffee', '/support', '/contact', '/admin/login']) {
    test(`${path} has zero serious or critical axe violations`, async ({ page }) => {
        await page.goto(path);
        await expect(page.getByRole('main')).toBeVisible();
        await expectNoSeriousA11yViolations(page);
    });
}

test.beforeAll(() => {
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        $user = App\\Models\\User::updateOrCreate(
            ['email' => '${email}'],
            ['name' => 'S5 axe administrator', 'email_verified_at' => now(), 'password' => getenv('E2E_TEST_PASSWORD'), 'role' => 'super_admin', 'is_active' => true]
        );
        App\\Models\\Lead::updateOrCreate(
            ['email' => 's5-a11y-lead@browser.test'],
            ['name' => '${leadName}', 'message' => 'Disposable axe lead workspace probe.', 'status' => 'new', 'assigned_to' => $user->id, 'last_activity_at' => now()]
        );
    `);
});

test.afterAll(() => {
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        App\\Models\\Lead::where('email', 's5-a11y-lead@browser.test')->delete();
        App\\Models\\User::where('email', '${email}')->delete();
    `);
});

for (const path of ['/admin', '/admin/coffee-products', '/admin/leads']) {
    test(`${path} has zero serious or critical axe violations`, async ({ page }) => {
        await page.goto('/admin/login');
        await loginAs(page, email, password);
        const response = await page.goto(path);
        expect(response?.status()).toBe(200);
        await expect(page.getByRole('main')).toBeVisible();
        await expectNoSeriousA11yViolations(page);
    });
}

test('lead workspace has zero serious or critical axe violations', async ({ page }) => {
    await page.goto('/admin/login');
    await loginAs(page, email, password);
    await page.goto('/admin/leads');
    await page.getByRole('row', { name: new RegExp(leadName) }).getByRole('link', { name: 'View' }).click();
    await expect(page.getByText(leadName, { exact: true })).toBeVisible();
    await expectNoSeriousA11yViolations(page);
});
