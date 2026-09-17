import SwiftUI

struct RootView: View {
    @EnvironmentObject private var session: SessionStore

    var body: some View {
        Group {
            if session.isRestoring {
                ProgressView("載入中…")
            } else {
                TabView {
                    NavigationStack { NewsListView() }
                        .tabItem { Label("最新消息", systemImage: "newspaper") }

                    NavigationStack { EventListView() }
                        .tabItem { Label("賽事報名", systemImage: "trophy") }

                    NavigationStack { MemberHomeView() }
                        .tabItem { Label("會員中心", systemImage: "person.crop.circle") }
                }
                .tint(.indigo)
            }
        }
    }
}

struct LoadingOverlay: ViewModifier {
    let isLoading: Bool

    func body(content: Content) -> some View {
        content.overlay {
            if isLoading {
                ZStack {
                    Color.black.opacity(0.08).ignoresSafeArea()
                    ProgressView().controlSize(.large)
                }
            }
        }
    }
}

extension View {
    func loading(_ active: Bool) -> some View { modifier(LoadingOverlay(isLoading: active)) }
}

struct ErrorStateView: View {
    let message: String
    let retry: () -> Void

    var body: some View {
        VStack(spacing: 14) {
            Image(systemName: "exclamationmark.triangle").font(.largeTitle).foregroundStyle(.secondary)
            Text("無法載入").font(.title2.bold())
            Text(message).foregroundStyle(.secondary).multilineTextAlignment(.center)
            Button("重新載入", action: retry)
                .buttonStyle(.borderedProminent)
        }
        .padding()
    }
}

struct EmptyStateView: View {
    let title: String
    let icon: String

    var body: some View {
        VStack(spacing: 14) {
            Image(systemName: icon).font(.largeTitle).foregroundStyle(.secondary)
            Text(title).font(.title2.bold())
        }
        .padding()
    }
}
