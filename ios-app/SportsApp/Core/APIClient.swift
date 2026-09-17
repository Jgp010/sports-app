import Foundation

struct APIEnvelope<Value: Decodable>: Decodable {
    let success: Bool
    let data: Value?
    let error: APIErrorPayload?
}

struct APIErrorPayload: Decodable {
    let code: String?
    let message: String?
    let details: [String: [String]]?
}

struct LegacyErrorEnvelope: Decodable {
    let message: String?
    let errors: [String: [String]]?
    let error: APIErrorPayload?
}

struct APIError: LocalizedError {
    let statusCode: Int
    let code: String?
    let displayMessage: String

    var errorDescription: String? { displayMessage }
}

actor APIClient {
    static let shared = APIClient()

    private let baseURL = URL(string: "https://purple-oyster-440244.hostingersite.com/api/v1/")!
    private let session: URLSession
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder

    init(session: URLSession = .shared) {
        self.session = session
        decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        encoder = JSONEncoder()
        encoder.keyEncodingStrategy = .convertToSnakeCase
    }

    func news() async throws -> [NewsPost] {
        try await request("news?per_page=50")
    }

    func newsDetail(slug: String) async throws -> NewsPost {
        try await request("news/\(path(slug))")
    }

    func events() async throws -> [SportsEvent] {
        try await request("events?per_page=50")
    }

    func event(slug: String) async throws -> SportsEvent {
        try await request("events/\(path(slug))")
    }

    func login(email: String, password: String) async throws -> AuthData {
        try await request("auth/login", method: "POST", body: ["email": email, "password": password])
    }

    func register(name: String, email: String, password: String, phone: String) async throws -> AuthData {
        try await request("auth/register", method: "POST", body: [
            "name": name, "email": email, "password": password, "phone": phone
        ])
    }

    func me(token: String) async throws -> Member {
        try await request("me", token: token)
    }

    func updateMe(token: String, body: MemberUpdateRequest) async throws -> Member {
        try await request("me", method: "PUT", body: body, token: token)
    }

    func registrations(token: String) async throws -> [Registration] {
        try await request("me/registrations", token: token)
    }

    func registerEvent(token: String, slug: String, body: RegistrationRequest) async throws -> Registration {
        try await request("events/\(path(slug))/registrations", method: "POST", body: body, token: token)
    }

    func cancelRegistration(token: String, id: Int) async throws {
        try await requestWithoutData("registrations/\(id)", method: "DELETE", token: token)
    }

    func logout(token: String) async throws {
        try await requestWithoutData("auth/logout", method: "POST", token: token)
    }

    private func request<Value: Decodable>(
        _ resource: String,
        method: String = "GET",
        body: (any Encodable)? = nil,
        token: String? = nil
    ) async throws -> Value {
        let data = try await perform(resource, method: method, body: body, token: token)
        do {
            let envelope = try decoder.decode(APIEnvelope<Value>.self, from: data)
            guard envelope.success, let value = envelope.data else {
                throw APIError(statusCode: 200, code: envelope.error?.code, displayMessage: envelope.error?.message ?? "服務回應異常，請稍後再試。")
            }
            return value
        } catch let error as APIError {
            throw error
        } catch {
            throw APIError(statusCode: 200, code: nil, displayMessage: "服務回應格式錯誤，請稍後再試。")
        }
    }

    private func requestWithoutData(
        _ resource: String,
        method: String,
        token: String
    ) async throws {
        _ = try await perform(resource, method: method, body: nil, token: token)
    }

    private func perform(
        _ resource: String,
        method: String,
        body: (any Encodable)?,
        token: String?
    ) async throws -> Data {
        guard let url = URL(string: resource, relativeTo: baseURL) else {
            throw APIError(statusCode: 0, code: nil, displayMessage: "網址格式錯誤。")
        }
        var request = URLRequest(url: url, timeoutInterval: 20)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json; charset=utf-8", forHTTPHeaderField: "Content-Type")
        request.setValue("ios", forHTTPHeaderField: "X-App-Platform")
        if let token { request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
        if let body { request.httpBody = try encode(body) }

        do {
            let (data, response) = try await session.data(for: request)
            guard let http = response as? HTTPURLResponse else {
                throw APIError(statusCode: 0, code: nil, displayMessage: "服務回應異常，請稍後再試。")
            }
            guard (200...299).contains(http.statusCode) else {
                throw parseError(data: data, statusCode: http.statusCode)
            }
            return data
        } catch let error as APIError {
            throw error
        } catch let error as URLError {
            let message = error.code == .timedOut ? "連線逾時，請檢查網路後再試。" : "目前無法連線，請檢查網路後再試。"
            throw APIError(statusCode: 0, code: nil, displayMessage: message)
        } catch {
            throw APIError(statusCode: 0, code: nil, displayMessage: "目前無法完成操作，請稍後再試。")
        }
    }

    private func parseError(data: Data, statusCode: Int) -> APIError {
        let payload = try? decoder.decode(LegacyErrorEnvelope.self, from: data)
        let details = payload?.error?.details ?? payload?.errors
        let firstDetail = details?.sorted(by: { $0.key < $1.key }).first?.value.first
        let message = payload?.error?.message ?? firstDetail ?? payload?.message ?? fallback(for: statusCode)
        return APIError(statusCode: statusCode, code: payload?.error?.code, displayMessage: message)
    }

    private func fallback(for statusCode: Int) -> String {
        switch statusCode {
        case 401: return "登入狀態已失效，請重新登入。"
        case 404: return "找不到指定資料。"
        case 409, 422: return "資料無法送出，請檢查後再試。"
        case 429: return "請求次數過多，請稍後再試。"
        case 500...599: return "系統忙碌中，請稍後再試。"
        default: return "目前無法完成操作，請稍後再試。"
        }
    }

    private func encode(_ value: any Encodable) throws -> Data {
        try encoder.encode(AnyEncodable(value))
    }

    private func path(_ value: String) -> String {
        value.addingPercentEncoding(withAllowedCharacters: .urlPathAllowed) ?? value
    }
}

private struct AnyEncodable: Encodable {
    private let encodeValue: (Encoder) throws -> Void

    init(_ value: any Encodable) {
        encodeValue = { encoder in
            try value.encode(to: encoder)
        }
    }

    func encode(to encoder: Encoder) throws {
        try encodeValue(encoder)
    }
}
