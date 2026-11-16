import { defineConfig } from '@playwright/test';

const port = Number(process.env.PLAYWRIGHT_PORT ?? 8001);
const baseURL = `http://127.0.0.1:${port}`;
const publicViewports = [360, 768, 1024, 1440] as const;
const roles = ['super_admin', 'catalog_manager', 'support'] as const;

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: '**/*.spec.{ts,js}',
    fullyParallel: false,
    workers: 1,
    reporter: 'list',
    outputDir: process.env.PLAYWRIGHT_OUTPUT_DIR ?? 'test-results',
    use: {
        baseURL,
        browserName: 'chromium',
        viewport: { width: 1280, height: 800 },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            testIgnore: [
                '**/full-public-matrix.spec.ts',
                '**/admin-role-matrix.spec.ts',
                '**/a11y-full-matrix.spec.ts',
            ],
        },
        ...publicViewports.map((width) => ({
            name: `public-${width}`,
            testMatch: '**/full-public-matrix.spec.ts',
            use: { viewport: { width, height: width === 360 ? 800 : 900 } },
        })),
        ...roles.map((role) => ({
            name: `role-${role}`,
            testMatch: '**/admin-role-matrix.spec.ts',
            metadata: { role },
        })),
        {
            name: 'a11y',
            testMatch: [
                '**/a11y-full-matrix.spec.ts',
                '**/public-accessibility.spec.ts',
                '**/auth-branding.spec.ts',
            ],
            use: { viewport: { width: 1440, height: 900 } },
        },
    ],
    webServer: {
        command: `bash scripts/prepare-browser-db.sh && CONTACT_THROTTLE_LIMIT=1000 LOGIN_THROTTLE_LIMIT=1000 DB_CONNECTION=mysql DB_HOST=\${DB_HOST:-127.0.0.1} DB_PORT=\${DB_PORT:-3306} DB_DATABASE=\${BROWSER_DB_DATABASE:-anduongiti_browser} DB_USERNAME=\${BROWSER_DB_ROOT_USER:-root} DB_PASSWORD=\${BROWSER_DB_ROOT_PASSWORD:-123456} php artisan serve --host=127.0.0.1 --port=${port}`,
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
