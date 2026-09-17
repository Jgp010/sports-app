import Foundation
import Combine
import Security

@MainActor
final class SessionStore: ObservableObject {
    @Published private(set) var token: String?
    @Published private(set) var member: Member?
    @Published private(set) var isRestoring = true

    var isLoggedIn: Bool { token != nil && member != nil }

    init() {
        token = KeychainStore.readToken()
    }

    func restore() async {
        defer { isRestoring = false }
        guard let token else { return }
        do {
            member = try await APIClient.shared.me(token: token)
        } catch {
            clearLocalSession()
        }
    }

    func login(email: String, password: String) async throws {
        let result = try await APIClient.shared.login(email: email, password: password)
        save(result)
    }

    func register(name: String, email: String, password: String, phone: String) async throws {
        let result = try await APIClient.shared.register(name: name, email: email, password: password, phone: phone)
        save(result)
    }

    func updateMember(_ request: MemberUpdateRequest) async throws {
        guard let token else { throw APIError(statusCode: 401, code: "UNAUTHENTICATED", displayMessage: "請先登入會員。") }
        member = try await APIClient.shared.updateMe(token: token, body: request)
    }

    func logout() async {
        if let token { try? await APIClient.shared.logout(token: token) }
        clearLocalSession()
    }

    func handleUnauthorized(_ error: Error) {
        guard let apiError = error as? APIError, apiError.statusCode == 401 else { return }
        clearLocalSession()
    }

    private func save(_ result: AuthData) {
        token = result.token
        member = result.user
        KeychainStore.saveToken(result.token)
    }

    private func clearLocalSession() {
        token = nil
        member = nil
        KeychainStore.deleteToken()
    }
}

private enum KeychainStore {
    private static let service = "com.example.sportsapp"
    private static let account = "member-api-token"

    static func saveToken(_ token: String) {
        deleteToken()
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecValueData as String: Data(token.utf8),
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly
        ]
        SecItemAdd(query as CFDictionary, nil)
    }

    static func readToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        var result: CFTypeRef?
        guard SecItemCopyMatching(query as CFDictionary, &result) == errSecSuccess,
              let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    static func deleteToken() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        SecItemDelete(query as CFDictionary)
    }
}
