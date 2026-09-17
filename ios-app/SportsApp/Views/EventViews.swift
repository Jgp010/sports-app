import SwiftUI

struct EventListView: View {
    @State private var events: [SportsEvent] = []
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        Group {
            if let errorMessage, events.isEmpty {
                ErrorStateView(message: errorMessage) { Task { await load() } }
            } else if events.isEmpty && !isLoading {
                EmptyStateView(title: "目前沒有賽事", icon: "trophy")
            } else {
                List(events) { event in
                    NavigationLink(value: event) { EventRow(event: event) }
                }
                .listStyle(.plain)
                .refreshable { await load() }
                .navigationDestination(for: SportsEvent.self) { EventDetailView(event: $0) }
            }
        }
        .navigationTitle("賽事報名")
        .loading(isLoading && events.isEmpty)
        .task { if events.isEmpty { await load() } }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            events = try await APIClient.shared.events()
            errorMessage = nil
        } catch { errorMessage = error.localizedDescription }
    }
}

private struct EventRow: View {
    let event: SportsEvent

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(event.sport.name).font(.caption).foregroundStyle(.indigo)
                Spacer()
                Text(event.registrationOpen ? "開放報名" : "尚未開放")
                    .font(.caption.bold())
                    .foregroundStyle(event.registrationOpen ? .green : .secondary)
            }
            Text(event.title).font(.headline)
            Label(AppFormatters.dateTime(event.eventStartAt), systemImage: "calendar")
                .font(.subheadline).foregroundStyle(.secondary)
            Label(event.venue, systemImage: "mappin.and.ellipse")
                .font(.subheadline).foregroundStyle(.secondary)
        }
        .padding(.vertical, 5)
    }
}

struct EventDetailView: View {
    @EnvironmentObject private var session: SessionStore
    let event: SportsEvent
    @State private var detail: SportsEvent?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showLogin = false
    @State private var showRegistration = false

    private var value: SportsEvent { detail ?? event }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 18) {
                Text(value.sport.name).font(.subheadline).foregroundStyle(.indigo)
                Text(value.title).font(.largeTitle.bold())
                InfoLine(icon: "calendar", title: "賽事時間", value: AppFormatters.dateTime(value.eventStartAt))
                InfoLine(icon: "mappin.and.ellipse", title: "地點", value: value.venue)
                InfoLine(icon: "clock", title: "報名截止", value: AppFormatters.dateTime(value.registrationCloseAt))
                if let capacity = value.capacity {
                    InfoLine(icon: "person.2", title: "名額", value: "\(value.registeredCount) / \(capacity)")
                }
                Divider()
                Text("賽事說明").font(.title2.bold())
                Text(value.description).lineSpacing(5).textSelection(.enabled)
                Text("賽事項目").font(.title2.bold())
                ForEach(value.items) { item in EventItemCard(item: item) }
                if let errorMessage { Text(errorMessage).foregroundStyle(.red).font(.footnote) }
                Button {
                    if session.isLoggedIn { showRegistration = true } else { showLogin = true }
                } label: {
                    Text(value.registrationOpen ? "立即報名" : "目前無法報名")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                .controlSize(.large)
                .disabled(!value.registrationOpen)
            }
            .padding()
        }
        .navigationTitle("賽事詳情")
        .navigationBarTitleDisplayMode(.inline)
        .loading(isLoading)
        .task { await load() }
        .sheet(isPresented: $showLogin) { NavigationStack { AuthenticationView() } }
        .sheet(isPresented: $showRegistration) {
            NavigationStack { RegistrationFormView(event: value) }
        }
        .onChange(of: session.isLoggedIn) { loggedIn in
            if loggedIn && showLogin {
                showLogin = false
                showRegistration = true
            }
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do { detail = try await APIClient.shared.event(slug: event.slug) }
        catch { errorMessage = error.localizedDescription }
    }
}

private struct EventItemCard: View {
    let item: EventItem

    var body: some View {
        VStack(alignment: .leading, spacing: 7) {
            HStack {
                Text(item.name).font(.headline)
                Spacer()
                Text(AppFormatters.money(item.currentFee)).font(.headline).foregroundStyle(.indigo)
            }
            if let description = item.description { Text(description).font(.subheadline).foregroundStyle(.secondary) }
            if item.earlyBirdActive {
                Label("早鳥優惠至 \(AppFormatters.dateTime(item.earlyBirdEndsAt))", systemImage: "bird")
                    .font(.caption).foregroundStyle(.orange)
            }
        }
        .padding()
        .background(Color(.secondarySystemBackground), in: RoundedRectangle(cornerRadius: 14))
    }
}

struct InfoLine: View {
    let icon: String
    let title: String
    let value: String

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: icon).foregroundStyle(.indigo).frame(width: 24)
            VStack(alignment: .leading, spacing: 2) {
                Text(title).font(.caption).foregroundStyle(.secondary)
                Text(value)
            }
        }
    }
}
