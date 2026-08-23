// Real-interaction verification for the sidebar hover-expand fix.
// Logs in via dev OTP, then drives actual mouse events:
//   enter rail -> expands; move deep into labels/scrollbar zone -> STAYS
//   expanded; leave -> collapses. Fails on any flicker (width oscillation).
const { chromium } = require('playwright');

(async () => {
  const base = process.env.BASE_URL || 'http://127.0.0.1:8010';
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

  // --- login (admin / dev OTP) ---
  await page.goto(base + '/login');
  await page.fill('#mobile', '09120000000');
  await page.click('button[type=submit]');
  await page.waitForSelector('#code', { timeout: 15000 });
  await page.fill('#code', '123456');
  await page.click('button[type=submit]');
  try {
    await page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 15000 });
  } catch (e) {
    const errText = await page.locator('.text-red-600, [class*=error]').allTextContents().catch(() => []);
    const body = (await page.content()).match(/ورود|کد[^<]*/g)?.slice(0,6);
    console.error('VERIFY DEBUG — url:', page.url(), '\nerrors:', errText, '\npage snippets:', body);
    throw e;
  }
  console.log('LOGIN OK ->', page.url());

  await page.goto(base + '/admin/lawyers');

  const aside = page.locator('aside').first();
  const box = await aside.boundingBox();
  if (!box) throw new Error('sidebar not found');

  const width = () => aside.evaluate((el) => el.getBoundingClientRect().width);
  const collapsed = await width();
  console.log('collapsed width:', collapsed);

  if (collapsed > 120) throw new Error('expected icon-rail collapsed state');

  // --- ICON-ONLY: every nav label must be display:none while collapsed ---
  const totalLabels = await page.locator('aside nav a > span:last-child').count();
  const hiddenLabels = await page.locator('aside nav a > span:last-child').evaluateAll(
    (els) => els.filter((el) => getComputedStyle(el).display === 'none').length
  );
  console.log(`collapsed labels hidden: ${hiddenLabels}/${totalLabels}`);
  if (hiddenLabels !== totalLabels) {
    throw new Error('PASS-3: some nav labels are visible in the collapsed rail');
  }

  // And nothing overflows the rail horizontally.
  const overflow = await aside.evaluate((el) => el.scrollWidth - el.clientWidth);
  console.log('horizontal overflow while collapsed:', overflow);
  if (overflow > 2) throw new Error('labels still occupy space / overflow the rail');

  // --- hover the rail (icon area) ---
  const railX = box.x + box.width / 2;
  const railY = box.y + 200;
  await page.mouse.move(railX, railY, { steps: 5 });
  await page.waitForTimeout(400); // transition duration
  const expanded = await width();
  console.log('expanded width:', expanded);
  if (expanded < 200) throw new Error('did not expand on hover');

  // --- THE FIX: move toward scrollbar/label zone and across content ---
  // Re-measure AFTER expansion (RTL: panel grows leftward from right edge).
  const b2 = await aside.boundingBox();
  const labelX = b2.x + 60;                      // over the labels
  const scrollX = b2.x + b2.width - 8;           // hard against the scrollbar
  const midY = b2.y + b2.height / 2;
  for (const [x, y] of [
    [labelX, railY],
    [scrollX, railY],
    [labelX, midY],
    [scrollX, midY + 120],
    [scrollX, b2.y + 20],                          // top corner / scrollbar start
    [(labelX + scrollX) / 2, b2.y + b2.height - 30], // bottom near logout
  ]) {
    await page.mouse.move(x, y, { steps: 4 });
    await page.waitForTimeout(350); // longer than transition; a flicker would collapse here
    const w = await width();
    console.log(`at (${x},${y}) width:`, w);
    if (w < 200) {
      throw new Error('FLICKER: sidebar collapsed while pointer was inside the expanded panel');
    }
  }

  // --- leave entirely -> collapses ---
  await page.mouse.move(box.x - 300, railY, { steps: 5 }); // into main content
  await page.waitForTimeout(400);
  const after = await width();
  console.log('after leave width:', after);
  if (after > 120) throw new Error('did not collapse after leaving');

  // Collapsed again -> labels hidden once more.
  const hiddenAfter = await page.locator('aside nav a > span:last-child').evaluateAll(
    (els) => els.filter((el) => getComputedStyle(el).display === 'none').length
  );
  if (hiddenAfter !== totalLabels) throw new Error('labels did not re-hide after collapse');

  // --- MOBILE drawer keeps labels visible (reuses session state; no
  //     second OTP needed, which would trip the resend cooldown) ---
  const state = await page.context().storageState();
  const mctx = await browser.newContext({ viewport: { width: 390, height: 844 }, storageState: state });
  const mob = await mctx.newPage();
  await mob.goto(base + '/admin'); // admin session: /dashboard is client-only
  console.log('[mobile] url after goto:', mob.url());
  const menuInfo = await mob.evaluate(() => ({
      url: location.pathname,
      title: document.title,
      bodyStart: document.body.innerHTML.slice(0, 400),
      buttonCount: document.querySelectorAll('button').length,
      asideCount: document.querySelectorAll('aside').length,
  }));
  console.log('[mobile debug]', JSON.stringify(menuInfo, null, 1));
  await mob.click('button[aria-label="منو"]');
  await mob.waitForTimeout(300);
  const mobLabelsVisible = await mob.locator('aside nav a > span:last-child').evaluateAll(
    (els) => els.filter((el) => getComputedStyle(el).display !== 'none').length
  );
  const mobTotal = await mob.locator('aside nav a > span:last-child').count();
  console.log(`mobile drawer labels visible: ${mobLabelsVisible}/${mobTotal}`);
  if (mobLabelsVisible !== mobTotal) throw new Error('mobile drawer lost its labels');
  await mob.close();

  // --- active-state spot check on this page ---
  const activeLabel = await page.locator('aside a[aria-current="page"] span:last-child').first().textContent();
  console.log('active nav item:', activeLabel.trim());
  if (!activeLabel.includes('وکلا')) throw new Error('wrong active item');

  console.log('\nALL INTERACTION CHECKS PASSED ✅');
  await browser.close();
})().catch((e) => {
  console.error('\nFAILED ❌:', e.message);
  process.exit(1);
});
