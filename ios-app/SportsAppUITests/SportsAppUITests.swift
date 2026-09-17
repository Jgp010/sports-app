import XCTest

final class SportsAppUITests: XCTestCase {
    override func setUpWithError() throws {
        continueAfterFailure = false
    }

    func testMainTabNavigation() throws {
        let app = XCUIApplication()
        app.launchArguments += ["-AppleLanguages", "(zh-Hant)", "-AppleLocale", "zh_TW", "-UITesting"]
        app.launch()

        let tabBar = app.tabBars.firstMatch
        XCTAssertTrue(tabBar.waitForExistence(timeout: 20), "主選單未在時限內顯示")

        let newsTab = tabBar.buttons["最新消息"]
        let eventsTab = tabBar.buttons["賽事報名"]
        let memberTab = tabBar.buttons["會員中心"]

        XCTAssertTrue(newsTab.exists, "缺少最新消息頁籤")
        XCTAssertTrue(eventsTab.exists, "缺少賽事報名頁籤")
        XCTAssertTrue(memberTab.exists, "缺少會員中心頁籤")

        memberTab.tap()
        XCTAssertTrue(app.navigationBars["會員中心"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.staticTexts["尚未登入"].waitForExistence(timeout: 5))

        eventsTab.tap()
        XCTAssertTrue(app.navigationBars["賽事報名"].waitForExistence(timeout: 5))

        newsTab.tap()
        XCTAssertTrue(app.navigationBars["最新消息"].waitForExistence(timeout: 5))

        let screenshot = XCUIScreen.main.screenshot()
        let attachment = XCTAttachment(screenshot: screenshot)
        attachment.name = "SportsApp-main-tabs"
        attachment.lifetime = .keepAlways
        add(attachment)
    }
}
