import { defineConfig } from '@playwright/test';
import * as dotenv from 'dotenv';
import * as path from 'path';
import { execSync } from 'child_process';

dotenv.config({ path: path.resolve(__dirname, '../../.env'), quiet: true });

const port = process.env.APP_PORT;
const host = `${process.env.PROJECT_NAME ?? 'innovationsbox'}.local`;
const BASE_URL = port ? `http://${host}:${port}` : `http://${host}`;
const PHP_SERVER_URL = 'http://localhost:6007';

/** Resolve the chromium executable.
 *  Priority: PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH env → `which chromium` → undefined (use playwright bundled). */
function resolveChromiumPath(): string | undefined {
  if (process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH) {
    return process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH;
  }
  try {
    return execSync('which chromium', { encoding: 'utf-8' }).trim();
  } catch {
    return undefined;
  }
}

const chromiumPath = resolveChromiumPath();
const isDocker = process.env.E2E_DOCKER === 'true';

export default defineConfig({
  testDir: './specs',
  timeout: isDocker ? 120_000 : 30_000,
  retries: 1,
  use: {
    baseURL: BASE_URL,
    headless: true,
  },
  projects: [
    {
      name: 'chromium',
      use: {
        browserName: 'chromium',
        launchOptions: {
          executablePath: chromiumPath,
          args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
      },
    },
    {
      name: 'e2e',
      testIgnore: /storybook\//,
      use: {
        baseURL: 'http://localhost:8880',
        browserName: 'chromium',
        trace: 'on',
        screenshot: 'on',
        video: 'on-first-retry',
        launchOptions: {
          executablePath: chromiumPath,
          args: ['--no-sandbox', '--disable-setuid-sandbox'],
        },
      },
    },
  ],
  webServer: isDocker ? [] : [
    {
      command: `php -S localhost:6007 ${path.resolve(__dirname, '../../storybook-server/index.php')}`,
      url: 'http://localhost:6007',
      reuseExistingServer: true,
      timeout: 10_000,
    },
    {
      command: 'php -S localhost:6008 tests/e2e/email-log-server/index.php',
      url: 'http://localhost:6008',
      reuseExistingServer: true,
      timeout: 10_000,
    },
  ],
});
