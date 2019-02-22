module.exports = {
	path: './features',
	screenshotsPath: './screenshots',
	screenshotsOnError: true,
	saveScreenshotsToDisk: true,
	saveScreenshotsToReport: true,
	webdriverio: {
		logLevel: 'silent',
		screenshotPath: './screenshots',
		desiredCapabilities: {
			chromeOptions: {
				args: [ 'headless', 'disable-gpu', '--no-sandbox' ]
			},
			isHeadless: true
		},
		debug: true
	}
};
