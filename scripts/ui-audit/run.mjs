import { chromium } from '@playwright/test';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { pages, sites, thresholds, viewports } from './config.mjs';

const root = process.cwd();
const requestedPage = process.argv.find((argument) => argument.startsWith('--page='))?.split('=')[1];
const selectedPages = requestedPage ? pages.filter((page) => page.name === requestedPage) : pages;

if (selectedPages.length === 0) {
    throw new Error(`Unknown page '${requestedPage}'.`);
}

const runId = new Date().toISOString().replaceAll(':', '-').replaceAll('.', '-');
const outputDirectory = path.join(root, 'storage', 'app', 'ui-audit', runId);
const browser = await chromium.launch({ headless: true });
const results = [];

const stableCss = `
    *, *::before, *::after {
        animation-delay: 0s !important;
        animation-duration: 0s !important;
        caret-color: transparent !important;
        scroll-behavior: auto !important;
        transition-delay: 0s !important;
        transition-duration: 0s !important;
    }
    html { scrollbar-width: none !important; }
    ::-webkit-scrollbar { display: none !important; }
`;

function normalizeText(value) {
    return value
        .replace(/\b\d+\s+items\b/gi, '')
        .replace(/\s+/g, ' ')
        .trim()
        .split(' ')
        .sort((a, b) => a.localeCompare(b))
        .join(' ');
}

function hash(value) {
    return createHash('sha256').update(value).digest('hex');
}

async function ensureDirectory(directory) {
    await mkdir(directory, { recursive: true });
}

async function settle(page) {
    await page.waitForLoadState('domcontentloaded');
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await page.evaluate(async () => {
        await document.fonts?.ready;
        for (let offset = 0; offset < document.documentElement.scrollHeight; offset += window.innerHeight) {
            window.scrollTo(0, offset);
            await new Promise((resolve) => setTimeout(resolve, 30));
        }
        const images = [...document.images];
        await Promise.race([
            Promise.all(images.map((image) => {
                if (image.complete) return Promise.resolve();
                return new Promise((resolve) => {
                    image.addEventListener('load', resolve, { once: true });
                    image.addEventListener('error', resolve, { once: true });
                });
            })),
            new Promise((resolve) => setTimeout(resolve, 12000)),
        ]);
        window.scrollTo(0, 0);
    });
    await page.waitForFunction(() => window.scrollY === 0);
    await page.waitForTimeout(1000);
    await page.addStyleTag({ content: stableCss });
    await page.waitForTimeout(250);
}

async function capture(siteName, pageConfig, viewportName, viewport) {
    const directory = path.join(outputDirectory, siteName, viewportName);
    await ensureDirectory(directory);

    const context = await browser.newContext({
        viewport,
        deviceScaleFactor: 1,
        colorScheme: 'light',
        locale: 'en-IN',
        timezoneId: 'Asia/Kolkata',
        reducedMotion: 'reduce',
    });
    const page = await context.newPage();
    page.setDefaultTimeout(90000);
    const consoleErrors = [];
    const failedRequests = [];

    page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
    });
    page.on('requestfailed', (request) => {
        if (request.failure()?.errorText === 'net::ERR_ABORTED') return;
        failedRequests.push({
            url: request.url(),
            error: request.failure()?.errorText ?? 'unknown',
        });
    });

    const url = new URL(pageConfig.path, sites[siteName]).toString();
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await settle(page);

    const screenshotPath = path.join(directory, `${pageConfig.name}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true, animations: 'disabled', timeout: 90000 });

    const manifest = await page.evaluate(() => {
        const visible = (element) => {
            const style = getComputedStyle(element);
            const rectangle = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && rectangle.width > 0 && rectangle.height > 0;
        };
        const bounds = (element) => {
            const rectangle = element.getBoundingClientRect();
            return {
                x: Math.round(rectangle.x * 100) / 100,
                y: Math.round((rectangle.y + window.scrollY) * 100) / 100,
                width: Math.round(rectangle.width * 100) / 100,
                height: Math.round(rectangle.height * 100) / 100,
            };
        };
        const descriptor = (element) => {
            const className = typeof element.className === 'string'
                ? element.className.trim().split(/\s+/).slice(0, 3).join('.')
                : '';
            return `${element.tagName.toLowerCase()}${element.id ? `#${element.id}` : ''}${className ? `.${className}` : ''}`;
        };

        const text = document.body.innerText.replace(/\s+/g, ' ').trim();
        const images = [...document.images].filter(visible).map((image) => ({
            src: image.currentSrc || image.src,
            alt: image.alt,
            naturalWidth: image.naturalWidth,
            naturalHeight: image.naturalHeight,
            ...bounds(image),
        }));
        const anchors = [...document.querySelectorAll('body > header, body > main > section, body > footer, main > section, main > article')]
            .filter(visible)
            .map((element, index) => ({ index, selector: descriptor(element), ...bounds(element) }));
        const landmarkDefinitions = [
            ['shipping', (text) => text.includes('Free Shipping On Orders Above ₹399')],
            ['hero-heading', (text) => text === 'Luxury Style Exclusive Designs'],
            ['philosophy', (text) => text.includes('THE MONRICX PHILOSOPHY')],
            ['product-count', (text) => /^\d+ products$/.test(text)],
            ['premium-collection', (text) => text === 'MONRICX Premium Collection'],
            ['contact', (text) => text === 'CONTACT US'],
            ['copyright', (text) => text.startsWith('© 2026 MONRICX')],
        ];
        const allElements = [...document.body.querySelectorAll('*')].filter(visible);
        const landmarks = Object.fromEntries(landmarkDefinitions.map(([name, predicate]) => {
            const candidates = allElements
                .map((element) => ({
                    element,
                    text: element.innerText?.replace(/\s+/g, ' ').trim() ?? '',
                    rectangle: element.getBoundingClientRect(),
                }))
                .filter((candidate) => predicate(candidate.text))
                .sort((a, b) => a.text.length - b.text.length || a.rectangle.y - b.rectangle.y || (a.rectangle.width * a.rectangle.height) - (b.rectangle.width * b.rectangle.height));
            return [name, candidates.length > 0 ? bounds(candidates[0].element) : null];
        }));

        return {
            title: document.title,
            finalUrl: location.href,
            document: {
                width: document.documentElement.scrollWidth,
                height: document.documentElement.scrollHeight,
            },
            text,
            images,
            anchors,
            landmarks,
        };
    });

    const captureResult = {
        site: siteName,
        page: pageConfig.name,
        viewport: viewportName,
        requestedUrl: url,
        status: response?.status() ?? null,
        screenshotPath,
        consoleErrors: [...new Set(consoleErrors)],
        failedRequests,
        ...manifest,
        textHash: hash(normalizeText(manifest.text)),
    };

    await writeFile(
        path.join(directory, `${pageConfig.name}.json`),
        `${JSON.stringify(captureResult, null, 2)}\n`,
    );
    await context.close();
    return captureResult;
}

function paddedImage(source, width, height) {
    const target = new PNG({ width, height, fill: true });
    PNG.bitblt(source, target, 0, 0, source.width, source.height, 0, 0);
    return target;
}

async function compareImages(livePath, stagingPath, diffPath) {
    const [live, staging] = await Promise.all([
        readFile(livePath).then(PNG.sync.read),
        readFile(stagingPath).then(PNG.sync.read),
    ]);
    const width = Math.max(live.width, staging.width);
    const height = Math.max(live.height, staging.height);
    const livePadded = paddedImage(live, width, height);
    const stagingPadded = paddedImage(staging, width, height);
    const diff = new PNG({ width, height });
    const differentPixels = pixelmatch(
        livePadded.data,
        stagingPadded.data,
        diff.data,
        width,
        height,
        { threshold: thresholds.pixelColorThreshold, includeAA: false },
    );
    await ensureDirectory(path.dirname(diffPath));
    await writeFile(diffPath, PNG.sync.write(diff));
    return {
        width,
        height,
        differentPixels,
        differencePercent: Math.round((differentPixels / (width * height)) * 100000) / 1000,
    };
}

function compareGeometry(live, staging) {
    const names = [...new Set([...Object.keys(live.landmarks), ...Object.keys(staging.landmarks)])];
    return names.map((name) => {
        const expected = live.landmarks[name] ?? null;
        const actual = staging.landmarks[name] ?? null;
        const deviation = expected && actual
            ? Math.max(
                Math.abs(expected.y - actual.y),
                Math.abs(expected.height - actual.height),
            )
            : null;
        return { name, expected, actual, maximumDeviation: deviation };
    });
}

for (const pageConfig of selectedPages) {
    for (const [viewportName, viewport] of Object.entries(viewports)) {
        process.stdout.write(`Capturing ${pageConfig.name} at ${viewportName}... `);
        const [live, staging] = await Promise.all([
            capture('live', pageConfig, viewportName, viewport),
            capture('staging', pageConfig, viewportName, viewport),
        ]);
        const diffPath = path.join(outputDirectory, 'diff', viewportName, `${pageConfig.name}.png`);
        const pixels = await compareImages(live.screenshotPath, staging.screenshotPath, diffPath);
        const geometry = compareGeometry(live, staging);
        const maximumAnchorDeviation = geometry.reduce(
            (maximum, item) => item.maximumDeviation === null ? Infinity : Math.max(maximum, item.maximumDeviation),
            0,
        );
        const checks = {
            liveHttp: live.status !== null && live.status >= 200 && live.status < 400,
            stagingHttp: staging.status !== null && staging.status >= 200 && staging.status < 400,
            textExact: live.textHash === staging.textHash,
            noConsoleErrors: staging.consoleErrors.length === 0,
            noFailedRequests: staging.failedRequests.length === 0,
            pixelDifference: pixels.differencePercent <= thresholds.pixelDifferencePercent,
            anchorGeometry: maximumAnchorDeviation <= thresholds.anchorDeviationPixels,
        };
        const passed = Object.values(checks).every(Boolean);
        results.push({
            page: pageConfig.name,
            path: pageConfig.path,
            viewport: viewportName,
            passed,
            checks,
            pixels,
            maximumAnchorDeviation,
            geometry,
            live: {
                status: live.status,
                finalUrl: live.finalUrl,
                document: live.document,
                textHash: live.textHash,
                imageCount: live.images.length,
                consoleErrors: live.consoleErrors,
                failedRequests: live.failedRequests,
            },
            staging: {
                status: staging.status,
                finalUrl: staging.finalUrl,
                document: staging.document,
                textHash: staging.textHash,
                imageCount: staging.images.length,
                consoleErrors: staging.consoleErrors,
                failedRequests: staging.failedRequests,
            },
            artifacts: {
                liveScreenshot: path.relative(outputDirectory, live.screenshotPath),
                stagingScreenshot: path.relative(outputDirectory, staging.screenshotPath),
                diff: path.relative(outputDirectory, diffPath),
            },
        });
        console.log(`${passed ? 'PASS' : 'FAIL'} (${pixels.differencePercent}% pixel difference)`);
    }
}

await browser.close();

const passedChecks = results.filter((result) => result.passed).length;
const summary = {
    runId,
    generatedAt: new Date().toISOString(),
    sites,
    thresholds,
    totals: {
        checks: results.length,
        passed: passedChecks,
        failed: results.length - passedChecks,
    },
    results,
};

await writeFile(path.join(outputDirectory, 'summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

const reportRows = results.map((result) => {
    const status = result.passed ? 'PASS' : 'FAIL';
    const text = result.checks.textExact ? 'PASS' : 'FAIL';
    const consoleStatus = result.checks.noConsoleErrors ? 'PASS' : `FAIL (${result.staging.consoleErrors.length})`;
    const requests = result.checks.noFailedRequests ? 'PASS' : `FAIL (${result.staging.failedRequests.length})`;
    const geometry = Number.isFinite(result.maximumAnchorDeviation)
        ? `${Math.round(result.maximumAnchorDeviation * 100) / 100}px`
        : 'structure mismatch';
    return `| ${result.page} | ${result.viewport} | ${status} | ${text} | ${result.pixels.differencePercent}% | ${geometry} | ${consoleStatus} | ${requests} |`;
});

const markdown = `# MONRICX UI parity report

- Generated: ${summary.generatedAt}
- Reference: ${sites.live}
- Staging: ${sites.staging}
- Passed: ${passedChecks}/${results.length}
- Pixel-difference tolerance: ${thresholds.pixelDifferencePercent}%
- Anchor-deviation tolerance: ${thresholds.anchorDeviationPixels}px

| Page | Viewport | Result | Text | Pixel diff | Anchor deviation | Console | Network |
|---|---|---:|---:|---:|---:|---:|---:|
${reportRows.join('\n')}
`;

await writeFile(path.join(outputDirectory, 'UI-PARITY-REPORT.md'), markdown);
console.log(`\nAudit complete: ${passedChecks}/${results.length} checks passed.`);
console.log(`Report: ${path.join(outputDirectory, 'UI-PARITY-REPORT.md')}`);

process.exitCode = passedChecks === results.length ? 0 : 1;
