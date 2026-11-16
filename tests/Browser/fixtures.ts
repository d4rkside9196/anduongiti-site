import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';

export { expect, test };
export type { Page };

export function browserPhp(code: string): string {
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

export function requireE2ePassword(): string {
    const password = process.env.E2E_TEST_PASSWORD;
    if (!password) {
        throw new Error('E2E_TEST_PASSWORD is required for the role and accessibility matrix.');
    }

    return password;
}

export async function loginAs(page: Page, email: string, password: string): Promise<void> {
    await page.getByLabel('Email address').fill(email);
    await page.getByRole('textbox', { name: 'Password*', exact: true }).fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL('**/admin');
}

export async function expectNoSeriousA11yViolations(page: Page): Promise<void> {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.evaluate(() => document.getAnimations().forEach((animation) => {
        if (animation.effect?.getComputedTiming().endTime === Infinity) {
            animation.cancel();
        } else {
            animation.finish();
        }
    }));
    for (const section of await page.locator('main section').all()) {
        await expect(section).toHaveCSS('opacity', '1');
    }

    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();
    const violations = results.violations.filter(({ impact }) =>
        impact === 'critical' || impact === 'serious',
    );
    const summary = violations.map(({ id, impact, description, nodes }) => ({
        id,
        impact,
        description,
        targets: nodes.map(({ target }) => target),
    }));
    const resultsPath = test.info().outputPath('axe-serious-critical-results.json');
    writeFileSync(resultsPath, JSON.stringify({ url: page.url(), violations: summary }, null, 2));
    await test.info().attach('axe-serious-critical-results', {
        path: resultsPath,
        contentType: 'application/json',
    });

    expect(summary, JSON.stringify(summary, null, 2)).toEqual([]);
}
