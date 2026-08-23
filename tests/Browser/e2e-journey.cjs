// ============================================================
// Adinet §36 End-to-End Journey (real browser, real Livewire)
// client registers -> lawyer registers+profile+review-submit ->
// admin verifies -> client requests -> lawyer accepts ->
// client pays (fake gateway, LOCAL only) -> lawyer completes ->
// client reviews -> admin approves -> public profile shows it.
//
// Usage:
//   php artisan serve --port=8030 &
//   BASE_URL=http://127.0.0.1:8030 node tests/Browser/e2e-journey.cjs
//   SKIP_PAYMENT=1 BASE_URL=https://dev.wishubest.com node ...   (live-safe)
// ============================================================
const { chromium } = require('playwright');

const base = process.env.BASE_URL || 'http://127.0.0.1:8030';
const SKIP_PAYMENT = !!process.env.SKIP_PAYMENT;
const stamp = Date.now().toString().slice(-6);

const clientMobile = '0937' + stamp + '1';
const lawyerMobile = '0937' + stamp + '2';
const LAWYER_NAME = 'وکیل ای۲ای ' + stamp;

let failures = 0;
const ok = (msg) => console.log('  ✔', msg);
const fail = (msg) => { console.error('  ✘', msg); failures++; };
const step = (msg) => console.log('\n== ' + msg);

async function makeOp(browser, storageState) {
  const ctx = await browser.newContext({
    viewport: { width: 1366, height: 900 },
    ...(storageState ? { storageState } : {}),
  });
  const page = await ctx.newPage();
  return { ctx, page };
}

async function otpLogin(page, mobile, urlPath) {
  await page.goto(base + urlPath);
  await page.fill('#mobile', mobile);
  await page.click('button[type=submit]');
  await page.waitForSelector('#code', { timeout: 20000 });
  await page.fill('#code', '123456'); // dev-mode fixed code
  await page.click('button[type=submit]');
  try {
    await page.waitForURL((u) => !u.pathname.includes(urlPath) && !u.pathname.startsWith('/login'), { timeout: 20000 });
  } catch (e) {
    const errs = await page.locator('.text-red-600').allTextContents().catch(() => []);
    console.error(`  otpLogin FAILED for ${mobile}: url=${page.url()} errors=`, JSON.stringify(errs));
    throw e;
  }
}

async function livewireCall(page, updates, method, params = []) {
  const csrf = await page.getAttribute('meta[name=csrf-token]', 'content');
  const snapEl = await page.locator('[wire\\:snapshot]').first();
  const snap = JSON.parse(htmlDecode(await snapEl.getAttribute('wire:snapshot')));
  const payload = {
    _token: csrf,
    components: [{ snapshot: snap, updates, calls: [{ method, params }] }],
  };
  const uri = await page.evaluate(() =>
    document.querySelector('script[data-update-uri]')?.dataset.updateUri
      ?? '/livewire-e1927e17/update'
  );
  const resp = await page.request.post(base + '/' + uri.replace(/^\//, ''), {
    data: payload,
    headers: {
      'X-CSRF-TOKEN': csrf,
      'X-Livewire': 'v4',
      'Referer': page.url(),
    },
  });
  if (resp.status() !== 200) throw new Error(`Livewire update ${resp.status()} for ${method}`);
  return JSON.parse(await resp.text());
}

function htmlDecode(s) { return s.replace(/&quot;/g, '"').replace(/&amp;/g, '&'); }

(async () => {
  const browser = await chromium.launch();
  const clientS = await makeOp(browser);
  const lawyerS = await makeOp(browser);
  const adminS = await makeOp(browser);

  // ===== 1) CLIENT registers =====
  step('CLIENT registers via OTP');
  await otpLogin(clientS.page, clientMobile, '/login');
  ok('client logged in');

  // ===== 2) LAWYER registers =====
  step('LAWYER registers via /register/lawyer');
  await otpLogin(lawyerS.page, lawyerMobile, '/register/lawyer');
  ok('lawyer logged in -> ' + lawyerS.page.url());

  // ===== 3) LAWYER completes professional profile + submits for review =====
  step('LAWYER completes profile and submits for review');
  let lp = lawyerS.page;
  const pr = await lp.goto(base + '/dashboard/lawyer/profile');
  try {
    await lp.waitForSelector('[wire\\:model="display_name"]', { timeout: 15000 });
  } catch (e) {
    console.error('DEBUG url:', lp.url(), '| title:', await lp.title());
    const c = await lp.content();
    console.error('has display_name:', c.includes('wire:model="display_name"'),
      '| Forbidden:', c.includes('Forbidden'));
    throw e;
  }
  await lp.fill('[wire\\:model="display_name"]', LAWYER_NAME);
  await lp.selectOption('[wire\\:model="city_id"]', { index: 1 });
  await lp.selectOption('[wire\\:model="bar_association"]', { index: 1 });
  await lp.fill('[wire\\:model="license_number"]', 'L-' + stamp);
  await lp.fill('[wire\\:model="years_of_experience"]', '7');
  await lp.fill('[wire\\:model="phone"]', '02188776655');
  await lp.locator('input[wire\\:model="specialty_ids"]').first().check();
  await lp.fill('[wire\\:model="bio"]', 'وکیل پایه یک دادگستری با سابقه طولانی در دعاوی ملکی و قراردادها.');
  await lp.click('button:text("ذخیره تغییرات")');
  await lp.waitForSelector('text=تغییرات ذخیره شد');
  ok('profile saved');
  await lp.click('button:text("ارسال برای تأیید")');
  await lp.waitForSelector('text=در انتظار بررسی توسط پشتیبانی');
  ok('submitted for review');

  // ===== 4) ADMIN verifies the lawyer =====
  step('ADMIN verifies the lawyer');
  await otpLogin(adminS.page, process.env.ADMIN_MOBILE || '09120000000', '/login');
  let ap = adminS.page;
  await ap.goto(base + '/admin/lawyers/verification');
  const row = ap.locator('div.rounded-2xl', { hasText: LAWYER_NAME }).first();
  await row.scrollIntoViewIfNeeded();
  await row.locator('button:text-is("تأیید")').click();
  await ap.waitForSelector('text=تأیید شد');
  ok('admin approved lawyer');

  // capture public slug for later checks
  await ap.goto(base + '/admin/lawyers');
  const lRow = ap.locator('tbody tr', { hasText: LAWYER_NAME }).first();
  const profHref = await lRow.locator('a[target="_blank"]').first().getAttribute('href');
  const slug = decodeURIComponent(profHref.split('/lawyers/')[1]);
  ok('public slug: ' + slug);

  // ===== 5) CLIENT discovers + views profile =====
  step('CLIENT discovers lawyer in public listing');
  let cp = clientS.page;
  await cp.goto(base + '/lawyers');
  await cp.getByPlaceholder('نام وکیل یا تخصص…').fill(stamp);
  await cp.waitForSelector('text=' + LAWYER_NAME);
  ok('visible in listing (search=' + stamp + ')');
  await cp.goto(base + '/lawyers/' + encodeURIComponent(slug));
  await cp.waitForSelector('text=درخواست مشاوره');
  ok('profile page renders with CTA');

  // ===== 6) CLIENT submits consultation request =====
  step('CLIENT submits consultation request');
  await cp.click('text=درخواست مشاوره');
  try {
    await cp.waitForSelector('text=انتخاب خدمت', { timeout: 15000 });
  } catch (e) {
    console.error('REQ DEBUG url:', cp.url(), '| title:', await cp.title());
    throw e;
  }
  // lawyer has no services yet -> create one as the lawyer first
  step('LAWYER creates a service');
  await lp.goto(base + '/dashboard/lawyer/services');
  try { await lp.click('button:text("خدمت جدید")'); } catch (e) { console.error('CLICK FAIL خدمت جدید:', e.message.split('\n')[0]); throw e; }
  await lp.fill('[wire\\:model="title"]', 'مشاوره تلفنی ۳۰ دقیقه');
  await lp.selectOption('[wire\\:model="consultation_type"]', 'phone');
  await lp.fill('[wire\\:model="duration_minutes"]', '30');
  await lp.fill('[wire\\:model="price_toman"]', '500000');
  await lp.click('button:text-is("افزودن")');
  await lp.waitForSelector('text=خدمت جدید اضافه شد');
  ok('service created');

  // now submit request
  await cp.goto(base + '/lawyers/' + encodeURIComponent(slug) + '/request');
  try {
    await cp.waitForSelector('text=انتخاب خدمت', { timeout: 15000 });
  } catch (e) {
    console.error('REQ DEBUG url:', cp.url(), '| title:', await cp.title());
    throw e;
  }
  await cp.locator('input[wire\\:model="service_id"]').first().check();
  await cp.fill('[wire\\:model="subject"]', 'اختلاف در قرارداد مشارکت ساختمانی');
  await cp.fill('[wire\\:model="description"]',
    'سلام، در قرارداد مشارکت با سازنده اختلاف داریم و به راهنمایی حقوقی نیاز فوری دارم لطفاً وقت بدهید.');
  try {
    await cp.locator('form button[type="submit"]').first().click({ timeout: 10000 });
  } catch (e) {
    console.error('SUBMIT DEBUG url:', cp.url(), '| title:', await cp.title(),
      '| btnCountAll:', await cp.locator('button').count(),
      '| hasSubmitTxt:', (await cp.content()).includes('ثبت درخواست مشاوره'),
      '| svcRadios:', await cp.locator('input[wire\\:model="service_id"]').count(),
      '| forms:', await cp.locator('form').count(),
      '| btnHtml:', await cp.evaluate(() => [...document.querySelectorAll('button')].map(b => b.textContent.trim().slice(0,30)).join(' || ')),
      '| err:', e.message.split('\n')[0]);
    throw e;
  }
  await cp.waitForURL('**/dashboard/requests**');
  await cp.waitForSelector('text=درخواست مشاوره شما ثبت شد');
  ok('request submitted');

  // ===== 7) LAWYER accepts with Jalali datetime =====
  step('LAWYER accepts the request');
  await lp.goto(base + '/dashboard/lawyer/requests');
  const pendRow = lp.locator('div.rounded-2xl', { hasText: 'اختلاف در قرارداد مشارکت ساختمانی' }).first();
  await pendRow.locator('button:text("پذیرش")').click();
  await pendRow.locator('[wire\\:model="accept_date_jalali"]').fill('1405/07/05');
  await pendRow.locator('[wire\\:model="accept_time"]').fill('10:00');
  await pendRow.locator('button:text("تأیید پذیرش")').click();
  await lp.waitForSelector('text=درخواست پذیرفته شد');
  ok('accepted with Jalali date');

  // ===== 8) CLIENT pays (LOCAL fake gateway only) =====
  if (!SKIP_PAYMENT) {
    step('CLIENT pays via gateway');
    await cp.goto(base + '/dashboard/appointments');
    await cp.click('a:text("پرداخت آنلاین")');
    await cp.waitForURL('**/dev/payments/simulate/**', { timeout: 20000 });
    await cp.click('a:text("پرداخت موفق")');
    await cp.waitForSelector('text=پرداخت با موفقیت انجام شد');
    ok('payment paid');
  } else {
    console.log('  (SKIP_PAYMENT=1 -> skipping gateway leg)');
  }

  // ===== 9) LAWYER completes the session =====
  step('LAWYER marks the session completed');
  await lp.goto(base + '/dashboard/lawyer/appointments');
  await lp.locator('button:text-is("برگزار شد")').first().click();
  await lp.waitForSelector('text=وضعیت نوبت به «برگزارشده»');
  ok('appointment completed');

  // ===== 10) CLIENT reviews =====
  step('CLIENT leaves a review');
  await cp.goto(base + '/dashboard/appointments');
  await cp.click('a:text("ثبت نظر درباره این مشاوره")');
  await cp.waitForSelector('text=امتیاز *');
  await cp.locator('button[aria-pressed]').nth(0).click(); // 5 stars (list is 5..1)
  await cp.fill('[wire\\:model="comment"]', 'برخورد حرفه‌ای و پاسخ‌های روشن؛ بسیار مفید بود.');
  await cp.click('button:text("ثبت نظر")');
  await cp.waitForURL('**/dashboard/reviews**');
  ok('review submitted (pending)');

  // ===== 11) ADMIN approves the review =====
  step('ADMIN approves the review');
  await ap.goto(base + '/admin/reviews');
  await ap.locator('button:text-is("تأیید")').first().click();
  await ap.waitForSelector('text=نظر تأییدشده شد');
  ok('review approved');

  // ===== 12) PUBLIC profile shows the review =====
  step('PUBLIC profile shows the approved review');
  const pubPage = await clientS.ctx.newPage();
  await pubPage.goto(base + '/lawyers/' + encodeURIComponent(slug));
  const pubHtml = await pubPage.content();
  if (!pubHtml.includes('برخورد حرفه‌ای')) throw new Error('approved review missing publicly');
  if (!pubHtml.includes('نظرات موکلان')) throw new Error('reviews section missing');
  ok('public profile shows review + rating section');

  // ===== RESULT =====
  console.log('\n==============================');
  if (failures) {
    console.error(`${failures} CHECK(S) FAILED ❌`);
    process.exitCode = 1;
  } else {
    console.log('E2E JOURNEY PASSED ✅ (all steps)');
  }
  await browser.close();
})().catch((e) => {
  console.error('\nE2E FAILED ❌:', e.message);
  process.exit(1);
});
