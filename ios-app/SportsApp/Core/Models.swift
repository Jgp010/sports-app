import Foundation

struct Sport: Codable, Identifiable, Hashable {
    let id: Int
    let name: String
    let slug: String
}

struct NewsPost: Codable, Identifiable, Hashable {
    let id: Int
    let slug: String
    let title: String
    let summary: String
    let content: String?
    let coverUrl: String?
    let sport: Sport
    let eventStartAt: String?
    let venue: String?
    let publishedAt: String?
}

struct EventItem: Codable, Identifiable, Hashable {
    let id: Int
    let name: String
    let description: String?
    let registrationFee: Int
    let earlyBirdFee: Int?
    let earlyBirdEndsAt: String?
    let currentFee: Int
    let earlyBirdActive: Bool
}

struct SportsEvent: Codable, Identifiable, Hashable {
    let id: Int
    let slug: String
    let title: String
    let description: String
    let venue: String
    let sport: Sport
    let eventStartAt: String
    let eventEndAt: String?
    let registrationOpenAt: String
    let registrationCloseAt: String
    let capacity: Int?
    let registeredCount: Int
    let registrationOpen: Bool
    let status: String?
    let items: [EventItem]
}

struct Member: Codable, Identifiable, Hashable {
    let id: Int
    var name: String
    var email: String
    var phone: String?
    var birthDate: String?
    var gender: String?
    var address: String?
    let ssoProvider: String?
    let hasSsoCredential: Bool
}

struct RegistrationItem: Codable, Identifiable, Hashable {
    let id: Int
    let name: String
    let unitPrice: Int
}

struct Registration: Codable, Identifiable, Hashable {
    let id: Int
    let registrationNo: String
    let status: String
    let contactPhone: String
    let organization: String?
    let totalAmount: Int
    let emergencyContactName: String?
    let emergencyContactPhone: String?
    let notes: String?
    let registeredAt: String
    let cancelledAt: String?
    let items: [RegistrationItem]
    let event: SportsEvent

    var statusText: String {
        switch status {
        case "registered": return "已報名"
        case "cancelled": return "已取消"
        case "attended": return "已出席"
        default: return status
        }
    }
}

struct AuthData: Codable {
    let token: String
    let tokenType: String
    let expiresAt: String
    let user: Member
}

struct RegistrationRequest: Encodable {
    let contactPhone: String
    let organization: String
    let emergencyContactName: String
    let emergencyContactPhone: String
    let itemIds: [Int]
    let notes: String?
}

struct MemberUpdateRequest: Encodable {
    let name: String
    let email: String
    let phone: String?
    let birthDate: String?
    let gender: String?
    let address: String?
}

struct EmptyResponse: Decodable {}
