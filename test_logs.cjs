const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
  await page.goto('http://localhost:8000/login');
  await page.fill('input[type="email"]', 'sollu.mart@email.com');
  await page.fill('input[type="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForNavigation();
  await page.goto('http://localhost:8000/customers');
  await page.waitForTimeout(2000);
  await browser.close();
})();
