
module.exports = {
  path: './features',
  screenshotsPath: './reports/screenshots',
  screenshotsOnError: true,
  saveScreenshotsToDisk: true,
  saveScreenshotsToReport: true,
  timeout: 99000,
  webdriverio: {
    logLevel: 'silent',
    screenshotPath: './reports/screenshots',
    desiredCapabilities: {
      chromeOptions: {
        args: ['headless', 'disable-gpu'],
      },
      isHeadless: true,
    },
    debug: true,
  },
};
