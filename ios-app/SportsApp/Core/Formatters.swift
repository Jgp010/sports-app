import Foundation

enum AppFormatters {
    private static let isoWithFractional: ISO8601DateFormatter = {
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        return formatter
    }()

    private static let iso = ISO8601DateFormatter()

    private static let dateTime: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "zh_Hant_TW")
        formatter.timeZone = TimeZone(identifier: "Asia/Taipei")
        formatter.dateFormat = "yyyy/MM/dd HH:mm"
        return formatter
    }()

    private static let currency: NumberFormatter = {
        let formatter = NumberFormatter()
        formatter.locale = Locale(identifier: "zh_Hant_TW")
        formatter.numberStyle = .currency
        formatter.currencyCode = "TWD"
        formatter.maximumFractionDigits = 0
        return formatter
    }()

    static func date(from value: String?) -> Date? {
        guard let value else { return nil }
        return isoWithFractional.date(from: value) ?? iso.date(from: value)
    }

    static func dateTime(_ value: String?) -> String {
        guard let date = date(from: value) else { return "—" }
        return dateTime.string(from: date)
    }

    static func money(_ value: Int) -> String {
        currency.string(from: NSNumber(value: value)) ?? "NT$ \(value)"
    }
}

extension String {
    var nilIfBlank: String? {
        let value = trimmingCharacters(in: .whitespacesAndNewlines)
        return value.isEmpty ? nil : value
    }
}
