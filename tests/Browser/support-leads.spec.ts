import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { expect, test } from './fixtures';

const email = 's4-support@browser.test';
const otherEmail = 's4-other-support@browser.test';
const password = process.env.SUPPORT_PASSWORD;
const unassignedName = 'S4 Browser unassigned enquiry';
const otherName = 'S4 Browser other owner enquiry';
let otherLeadId = '';

function browserPhp(code: string): string {
    return execFileSync('php', ['-r', code], {
        cwd: process.cwd(),
        env: {
            ...process.env,
            DB_CONNECTION: 'mysql',
            DB_HOST: '127.0.0.1',
            DB_PORT: '3306',
            DB_DATABASE: process.env.BROWSER_DB_DATABASE ?? 'anduongiti_browser',
            DB_USERNAME: process.env.BROWSER_DB_ROOT_USER ?? 'root',
            DB_PASSWORD: process.env.BROWSER_DB_ROOT_PASSWORD ?? '123456',
        },
        encoding: 'utf8',
    });
}

test.beforeAll(() => {
    test.skip(!password, 'Set SUPPORT_PASSWORD for the disposable browser database.');
    otherLeadId = browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        $password = getenv('SUPPORT_PASSWORD');
        $support = App\\Models\\User::create(['name' => 'S4 Support', 'email' => '${email}', 'email_verified_at' => now(), 'password' => $password, 'role' => 'support', 'is_active' => true]);
        $other = App\\Models\\User::create(['name' => 'S4 Other Support', 'email' => '${otherEmail}', 'email_verified_at' => now(), 'password' => $password, 'role' => 'support', 'is_active' => true]);
        App\\Models\\Lead::create(['name' => '${unassignedName}', 'email' => 'unassigned@browser.test', 'message' => 'Disposable browser enquiry', 'status' => 'new', 'last_activity_at' => now()]);
        $lead = App\\Models\\Lead::create(['name' => '${otherName}', 'email' => 'other@browser.test', 'message' => 'Disposable browser enquiry', 'status' => 'new', 'assigned_to' => $other->id, 'last_activity_at' => now()]);
        echo $lead->id;
    `).trim();
});

test.afterAll(() => {
    if (!password) return;
    browserPhp(`
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        App\\Models\\Lead::whereIn('name', ['${unassignedName}', '${otherName}'])->delete();
        App\\Models\\User::whereIn('email', ['${email}', '${otherEmail}'])->delete();
    `);
});

test('support processes an unassigned lead without accessing another owner record', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByRole('textbox', { name: 'Password*', exact: true }).fill(password!);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL('**/admin');

    await page.goto('/admin/leads');
    const row = page.getByRole('row', { name: new RegExp(unassignedName) });
    await expect(row).toBeVisible();
    await expect(page.getByRole('row', { name: new RegExp(otherName) })).toHaveCount(0);
    await row.getByRole('button', { name: 'Claim' }).click();
    await page.getByRole('button', { name: 'Confirm' }).click();
    await expect(row.getByText(email, { exact: true })).toBeVisible();

    await row.getByRole('link', { name: 'View' }).click();
    await page.locator('[wire\\:key*="NotesRelationManager"]').scrollIntoViewIfNeeded();
    await page.getByRole('button', { name: 'Add note' }).click();
    await page.getByLabel('Internal note').fill('Browser internal note');
    await page.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(page.getByText('Browser internal note')).toBeVisible();

    await page.goto('/admin/leads');
    const statusModal = page.locator('.fi-modal-window-ctn');
    await row.getByRole('button', { name: 'Change status' }).click();
    await statusModal.locator('select').selectOption('in_progress');
    await statusModal.getByRole('button', { name: 'Submit', exact: true }).click();
    await row.getByRole('button', { name: 'Change status' }).click();
    await statusModal.locator('select').selectOption('replied');
    await statusModal.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(row.getByText('replied', { exact: true })).toBeVisible();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Export CSV', exact: true }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/\.csv$/);
    const csv = readFileSync((await download.path())!, 'utf8');
    expect(csv).toContain(unassignedName);
    expect(csv).toContain(email);
    expect(csv).toContain('replied');
    expect(csv).not.toContain(otherName);
    expect(csv).not.toContain(otherEmail);

    const response = await page.goto(`/admin/leads/${otherLeadId}`);
    expect(response?.status()).toBe(404);
});
